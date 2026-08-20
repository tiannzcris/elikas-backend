<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EvacuationCenter;
use App\Models\HazardProneArea;

class GisController extends Controller
{
    /**
     * Single call the map page makes on load -- returns evacuation centers
     * and hazard zones both as standard GeoJSON FeatureCollections, so the
     * frontend can feed each straight into a Leaflet L.geoJSON() layer
     * without any reshaping.
     */
    public function mapData()
    {
        // A GeoJSON Point requires real coordinates -- centers without a
        // confirmed map location yet (latitude/longitude are nullable) are
        // excluded here rather than plotted at a fake (0,0) fallback.
        $centerFeatures = EvacuationCenter::with('barangay')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function (EvacuationCenter $center) {
                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [(float) $center->longitude, (float) $center->latitude],
                    ],
                    'properties' => [
                        'id' => $center->id,
                        'kind' => 'evacuation_center',
                        'name' => $center->name,
                        'type' => $center->type,
                        'status' => $center->status,
                        'barangay' => $center->barangay?->name,
                        'capacity_persons' => $center->capacity_persons,
                        'current_occupancy' => $center->currentOccupancy(),
                        'occupancy_percent' => $center->occupancyPercent(),
                        'photo_url' => $center->photo_url,
                    ],
                ];
            })->values();

        $hazardFeatures = HazardProneArea::with('mapLayers')->get()
            ->map(function (HazardProneArea $area) {
                $layer = $area->mapLayers->first();

                if (! $layer) {
                    return null; // shouldn't happen (created together in HazardProneAreaController::store) but guard anyway
                }

                return [
                    'type' => 'Feature',
                    'geometry' => $layer->geojson_data,
                    'properties' => [
                        'id' => $area->id,
                        'kind' => 'hazard_area',
                        'area_name' => $area->area_name,
                        'hazard_type' => $area->hazard_type,
                        'description' => $area->description,
                    ],
                ];
            })
            ->filter()
            ->values();

        return $this->success([
            'evacuation_centers' => ['type' => 'FeatureCollection', 'features' => $centerFeatures],
            'hazard_areas' => ['type' => 'FeatureCollection', 'features' => $hazardFeatures],
        ]);
    }
}
