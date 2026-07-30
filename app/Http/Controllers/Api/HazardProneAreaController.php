<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\HazardProneArea\StoreHazardProneAreaRequest;
use App\Http\Resources\HazardProneAreaResource;
use App\Models\HazardProneArea;
use App\Models\MapLayer;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HazardProneAreaController extends Controller
{
    public function index(Request $request)
    {
        $query = HazardProneArea::query()->with(['barangay', 'mapLayers']);

        if ($request->filled('hazard_type')) {
            $query->where('hazard_type', $request->string('hazard_type'));
        }

        return $this->success(HazardProneAreaResource::collection($query->get()));
    }

    public function show(HazardProneArea $hazardProneArea)
    {
        return $this->success(
            new HazardProneAreaResource($hazardProneArea->load(['barangay', 'mapLayers']))
        );
    }

    /**
     * Stores a hazard zone in TWO places from one GeoJSON payload:
     *  - hazard_prone_areas.geometry (spatial POLYGON, converted to WKT)
     *    -> enables server-side "is point X inside any hazard zone" queries
     *  - a map_layers row (raw GeoJSON, as-is)
     *    -> what the map actually fetches to draw the polygon; skips
     *       reconstructing GeoJSON from WKT on every read
     */
    public function store(StoreHazardProneAreaRequest $request)
    {
        $validated = $request->validated();

        $hazardArea = DB::transaction(function () use ($validated, $request) {
            $wkt = $this->geoJsonPolygonToWkt($validated['geojson']['coordinates']);

            $hazardArea = new HazardProneArea([
                'barangay_id' => $validated['barangay_id'] ?? null,
                'area_name' => $validated['area_name'],
                'hazard_type' => $validated['hazard_type'],
                'description' => $validated['description'] ?? null,
            ]);
            $hazardArea->pendingGeometryWkt = $wkt;
            $hazardArea->save();

            MapLayer::create([
                'hazard_area_id' => $hazardArea->id,
                'layer_name' => $validated['area_name'],
                'layer_type' => $validated['hazard_type'],
                'geojson_data' => $validated['geojson'],
            ]);

            SystemLog::create([
                'user_id' => $request->user()->id,
                'action' => 'hazard_area.created',
                'description' => "{$request->user()->name} mapped a new {$validated['hazard_type']} zone: '{$validated['area_name']}'.",
                'ip_address' => $request->ip(),
            ]);

            return $hazardArea;
        });

        return $this->success(
            new HazardProneAreaResource($hazardArea->fresh(['barangay', 'mapLayers'])),
            'Hazard zone mapped successfully.',
            201
        );
    }

    public function destroy(Request $request, HazardProneArea $hazardProneArea)
    {
        $name = $hazardProneArea->area_name;
        $hazardProneArea->delete(); // cascades to map_layers per its migration's FK

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'hazard_area.deleted',
            'description' => "{$request->user()->name} removed hazard zone '{$name}'.",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(null, 'Hazard zone removed successfully.');
    }

    /**
     * Converts a GeoJSON Polygon's coordinate array into WKT. Only the
     * exterior ring (index 0) is used -- holes/interior rings aren't part
     * of this system's scope, hazard zones are drawn as simple polygons.
     */
    private function geoJsonPolygonToWkt(array $coordinates): string
    {
        $ring = $coordinates[0];
        $points = array_map(fn ($point) => "{$point[0]} {$point[1]}", $ring);

        return 'POLYGON((' . implode(', ', $points) . '))';
    }
}
