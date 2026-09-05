<?php

namespace Database\Seeders;

use App\Models\HazardProneArea;
use App\Models\MapLayer;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Real hazard zone for Ligao City's official "Coastal Unit" (Barangays
 * Cabarian, Catburawan, Maonon per the LGU's own 4-unit geographic
 * classification) -- a storm-surge zone whose polygon is a pre-computed
 * convex hull (14 manually-collected coastal reference points, hulled
 * via scipy) used here exactly as originally computed, not regenerated
 * or approximated further.
 *
 * Mirrors HazardProneAreaController::store() exactly (same two-table
 * write: hazard_prone_areas.geometry as WKT via the model's saving()
 * hook, plus a map_layers row holding the raw GeoJSON Leaflet actually
 * renders) -- same pattern already used for the Mayon volcanic zones
 * (see MayonHazardZonesSeeder).
 *
 * barangay_id is left NULL: this zone spans three barangays, so pinning
 * it to a single barangay_id would misrepresent its actual extent --
 * same reasoning as the Mayon zones spanning multiple barangays.
 *
 * Idempotent: skips if a hazard_prone_areas row with this exact
 * area_name already exists.
 *
 * NOT part of the default `php artisan db:seed` chain (see
 * DatabaseSeeder) -- run explicitly:
 *   php artisan db:seed --class=CoastalStormSurgeZoneSeeder
 */
class CoastalStormSurgeZoneSeeder extends Seeder
{
    private const AREA_NAME = 'Ligao City Coastal Unit - Storm Surge Zone';

    // Pre-computed convex hull, closed ring, [lng, lat] order -- used
    // exactly as originally computed, not regenerated here.
    private const EXTERIOR_RING = [
        [123.38599162368445, 13.10177574306838],
        [123.38719325319951, 13.041077269402763],
        [123.35835414483736, 13.021342873472634],
        [123.32551030645568, 13.007695884600103],
        [123.32006005829794, 13.006901414879279],
        [123.3080008478072, 13.023459310029967],
        [123.38599162368445, 13.10177574306838],
    ];

    public function run(): void
    {
        if (HazardProneArea::where('area_name', self::AREA_NAME)->exists()) {
            $this->command->info('Skipped (already exists): '.self::AREA_NAME);

            return;
        }

        $geojson = ['type' => 'Polygon', 'coordinates' => [self::EXTERIOR_RING]];
        $wkt = $this->geoJsonPolygonToWkt([self::EXTERIOR_RING]);
        $distributedBy = User::first();

        DB::transaction(function () use ($geojson, $wkt, $distributedBy) {
            $hazardArea = new HazardProneArea([
                'barangay_id' => null,
                'area_name' => self::AREA_NAME,
                'hazard_type' => 'storm_surge',
                'description' => 'Approximate storm-surge-exposed area covering Ligao City\'s official Coastal '.
                    'Unit (Barangays Cabarian, Catburawan, and Maonon, per the LGU\'s own 4-unit geographic '.
                    'classification). Boundary is a convex-hull approximation from manually-collected coastal '.
                    'reference points, not an official PAGASA storm surge advisory model output -- a reasonable '.
                    'capstone-level approximation, not a claim of matching an official storm-surge simulation.',
            ]);
            $hazardArea->pendingGeometryWkt = $wkt;
            $hazardArea->save();

            MapLayer::create([
                'hazard_area_id' => $hazardArea->id,
                'layer_name' => self::AREA_NAME,
                'layer_type' => 'storm_surge',
                'geojson_data' => $geojson,
            ]);

            if ($distributedBy) {
                SystemLog::create([
                    'user_id' => $distributedBy->id,
                    'action' => 'hazard_area.created',
                    'description' => 'Seeded real hazard zone \''.self::AREA_NAME.'\' via CoastalStormSurgeZoneSeeder.',
                    'ip_address' => null,
                ]);
            }
        });

        $this->command->info(sprintf(
            'Created: %s (storm_surge, %d points)',
            self::AREA_NAME, count(self::EXTERIOR_RING)
        ));
    }

    // Same conversion HazardProneAreaController::geoJsonPolygonToWkt() does
    // -- duplicated here (that method is private) rather than modifying the
    // controller just to expose it for a seeder.
    private function geoJsonPolygonToWkt(array $coordinates): string
    {
        $ring = $coordinates[0];
        $points = array_map(fn ($point) => "{$point[0]} {$point[1]}", $ring);

        return 'POLYGON(('.implode(', ', $points).'))';
    }
}
