<?php

namespace Database\Seeders;

use App\Models\HazardProneArea;
use App\Models\MapLayer;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Real PHIVOLCS-sourced hazard zones for Mayon Volcano: the Permanent
 * Danger Zone (6km radius) and Extended Danger Zone (7km radius), both
 * circular polygons centered on the summit (13.2570N, 123.6856E).
 *
 * Mirrors HazardProneAreaController::store() exactly (same two-table
 * write: hazard_prone_areas.geometry as WKT via the model's saving()
 * hook, plus a map_layers row holding the raw GeoJSON Leaflet actually
 * renders) -- this seeder doesn't invent a new persistence path, it just
 * drives the existing, already-correct one with real circle-polygon data
 * instead of a hand-drawn Leaflet.draw shape.
 *
 * barangay_id is left NULL for both: these zones span many barangays
 * around the volcano (a 6-7km radius circle is far larger than any one
 * barangay), so pinning them to a single barangay_id would misrepresent
 * their actual extent. hazard_prone_areas.barangay_id is nullable
 * precisely to support exactly this case.
 *
 * The Extended Danger Zone is deliberately a full circle, not PHIVOLCS's
 * real sector-specific enforcement shape -- see this zone's description
 * field for the explicit caveat.
 *
 * Idempotent: skips a zone entirely if a hazard_prone_areas row with
 * that exact area_name already exists.
 *
 * NOT part of the default `php artisan db:seed` chain (see
 * DatabaseSeeder) -- run explicitly:
 *   php artisan db:seed --class=MayonHazardZonesSeeder
 */
class MayonHazardZonesSeeder extends Seeder
{
    private const SUMMIT_LAT = 13.2570;

    private const SUMMIT_LNG = 123.6856;

    private const NUM_POINTS = 48;

    public function run(): void
    {
        $zones = [
            [
                'area_name' => 'Mayon Volcano - Permanent Danger Zone (6km)',
                'hazard_type' => 'volcanic_danger_zone',
                'description' => 'Official PHIVOLCS Permanent Danger Zone -- 6km radius circular exclusion '.
                    'zone around Mayon Volcano\'s summit, permanently enforced regardless of alert level.',
                'radius_km' => 6,
            ],
            [
                'area_name' => 'Mayon Volcano - Extended Danger Zone (7km)',
                'hazard_type' => 'volcanic_danger_zone',
                'description' => 'Official PHIVOLCS Extended Danger Zone -- 7km radius from Mayon\'s summit. '.
                    'Note: PHIVOLCS\'s real enforcement is sector-specific (historically SE/SSW/ENE flanks '.
                    'depending on activity; the south/southwest sector specifically during the 2026 unrest, '.
                    'matching Ligao City\'s direction from the summit) -- this system represents it as a full '.
                    'circle for simplicity, not an exact match to PHIVOLCS\'s precise enforced boundary shape.',
                'radius_km' => 7,
            ],
        ];

        $distributedBy = User::first();

        foreach ($zones as $zone) {
            if (HazardProneArea::where('area_name', $zone['area_name'])->exists()) {
                $this->command->info("Skipped (already exists): {$zone['area_name']}");

                continue;
            }

            $coordinates = [$this->circlePolygonCoordinates(self::SUMMIT_LAT, self::SUMMIT_LNG, $zone['radius_km'], self::NUM_POINTS)];
            $geojson = ['type' => 'Polygon', 'coordinates' => $coordinates];
            $wkt = $this->geoJsonPolygonToWkt($coordinates);

            DB::transaction(function () use ($zone, $geojson, $wkt, $distributedBy) {
                $hazardArea = new HazardProneArea([
                    'barangay_id' => null,
                    'area_name' => $zone['area_name'],
                    'hazard_type' => $zone['hazard_type'],
                    'description' => $zone['description'],
                ]);
                $hazardArea->pendingGeometryWkt = $wkt;
                $hazardArea->save();

                MapLayer::create([
                    'hazard_area_id' => $hazardArea->id,
                    'layer_name' => $zone['area_name'],
                    'layer_type' => $zone['hazard_type'],
                    'geojson_data' => $geojson,
                ]);

                if ($distributedBy) {
                    SystemLog::create([
                        'user_id' => $distributedBy->id,
                        'action' => 'hazard_area.created',
                        'description' => "Seeded official PHIVOLCS hazard zone '{$zone['area_name']}' via MayonHazardZonesSeeder.",
                        'ip_address' => null,
                    ]);
                }
            });

            $this->command->info(sprintf(
                'Created: %s (%dkm radius, %d points)',
                $zone['area_name'], $zone['radius_km'], count($coordinates[0])
            ));
        }
    }

    /**
     * Great-circle "destination point given distance and bearing" formula
     * -- generates a true geodesic circle (not a naive flat degree-offset
     * approximation), so the shape stays a real circle at this latitude
     * rather than a flattened ellipse. $i % $numPoints on the final
     * iteration reproduces bearing 0 exactly, so the first and last
     * coordinate are identical -- a properly closed GeoJSON LinearRing.
     */
    private function circlePolygonCoordinates(float $centerLat, float $centerLng, float $radiusKm, int $numPoints): array
    {
        $earthRadiusKm = 6371.0088;
        $angularDistance = $radiusKm / $earthRadiusKm;
        $lat0 = deg2rad($centerLat);
        $lng0 = deg2rad($centerLng);

        $points = [];
        for ($i = 0; $i <= $numPoints; $i++) {
            $bearing = deg2rad(360.0 * ($i % $numPoints) / $numPoints);
            $lat = asin(sin($lat0) * cos($angularDistance) + cos($lat0) * sin($angularDistance) * cos($bearing));
            $lng = $lng0 + atan2(
                sin($bearing) * sin($angularDistance) * cos($lat0),
                cos($angularDistance) - sin($lat0) * sin($lat)
            );
            $points[] = [round(rad2deg($lng), 6), round(rad2deg($lat), 6)];
        }

        return $points;
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
