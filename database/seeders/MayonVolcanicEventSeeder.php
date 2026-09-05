<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\Evacuee;
use App\Models\EvacuationCenter;
use App\Models\EvacuationEvent;
use App\Models\EvacuationRecord;
use App\Models\Family;
use Illuminate\Database\Seeder;

/**
 * One-time import of a REAL historical disaster event: the Mayon Volcano
 * Alert Level 3 eruption response, sourced from an official DSWD-DRMB
 * DROMIC report (partial report as of May 25, 2026).
 *
 * Unlike DemoDisasterDataSeeder, this is NOT fabricated/demo data and NOT
 * meant to be repeatedly wiped and recreated:
 * - The event and family/person COUNTS (25 families, 93 persons: 19
 *   families/76 persons inside Baligang Elementary School, 6 families/17
 *   persons outside a center) are real aggregate figures from the source
 *   report.
 * - Individual names ARE placeholders ("Household N Member M") -- the
 *   source report only contains aggregate statistics, not named individual
 *   records, so there is no real name data to import. This is a distinct
 *   case from DemoDisasterDataSeeder's fully-fabricated demo people: the
 *   EVENT and its counts are real, only the per-person breakdown isn't.
 * - contact_number is left NULL (not the demo seeder's "0907" placeholder
 *   prefix), since that prefix convention exists specifically to mark
 *   obviously-fictional test data -- these people are real (aggregate),
 *   just anonymous.
 *
 * rainfall_mm/max_wind_speed_kph are deliberately left NULL: a volcanic
 * event has no meaningful rainfall/wind reading, and PredictiveAnalyticsService
 * ::refreshTrainingSnapshot() only pulls closed events where BOTH fields are
 * non-null -- so this event is correctly excluded from the typhoon-only
 * rainfall/wind regression rather than polluting it with meaningless zeros.
 *
 * Idempotent: skips entirely if an event with this exact name already
 * exists. NOT chained into the default `php artisan db:seed` run (see
 * DatabaseSeeder) and NEVER wiped by DemoDisasterDataSeeder or any other
 * seeder -- this is real, permanent operational data. Run explicitly:
 *   php artisan db:seed --class=MayonVolcanicEventSeeder --force
 */
class MayonVolcanicEventSeeder extends Seeder
{
    // Ligao City center point -- Barangay::centroid_latitude/longitude are
    // never populated by BarangaySeeder (name/psgc_code only), so this is
    // the same fallback DemoDisasterDataSeeder uses for its sample centers.
    private const CITY_CENTER_LAT = 13.1391;

    private const CITY_CENTER_LNG = 123.5321;

    private const EVENT_NAME = 'Mayon Volcano Alert Level 3 (2026)';

    private const BARANGAY_NAME = 'Baligang';

    private const CENTER_NAME = 'Baligang Elementary School';

    // Real aggregate figures from the DSWD-DRMB DROMIC report (as of
    // 2026-05-25): family size mixes are constructed, not reported, but
    // are chosen so each group's family/person totals match the report
    // exactly (inside: 19 families / 76 persons; outside: 6 families /
    // 17 persons).
    private const INSIDE_CENTER_FAMILY_SIZES = [
        3, 3, 3, 3, 3,          // 5 families x 3 = 15
        4, 4, 4, 4, 4, 4, 4, 4, 4, // 9 families x 4 = 36
        5, 5, 5, 5, 5,          // 5 families x 5 = 25
    ]; // 19 families, 76 persons

    private const OUTSIDE_CENTER_FAMILY_SIZES = [
        2,                      // 1 family x 2 = 2
        3, 3, 3, 3, 3,          // 5 families x 3 = 15
    ]; // 6 families, 17 persons

    public function run(): void
    {
        if (EvacuationEvent::where('name', self::EVENT_NAME)->exists()) {
            $this->command->info('Skipped (already exists): '.self::EVENT_NAME);

            return;
        }

        $barangay = Barangay::where('name', self::BARANGAY_NAME)->first();

        if (! $barangay) {
            $this->command->error('Barangay "'.self::BARANGAY_NAME.'" not found -- run BarangaySeeder first. Aborting.');

            return;
        }

        $event = EvacuationEvent::create([
            'name' => self::EVENT_NAME,
            'event_type' => 'volcanic_eruption',
            'alert_level' => 'Alert Level 3',
            'max_wind_speed_kph' => null,
            'rainfall_mm' => null,
            'start_date' => '2026-01-06',
            'end_date' => '2026-05-25',
            'status' => 'closed',
            'description' => 'Based on official DSWD-DRMB DROMIC report data (Mayon Alert Level 3, '.
                'partial report as of May 25, 2026). Family/member records reflect real aggregate '.
                'counts from this report; individual names are placeholders as the source report '.
                'contains aggregate statistics only, not named individual records.',
        ]);

        $this->command->info(sprintf(
            'Created event: %s (event_type=volcanic_eruption, alert_level=Alert Level 3, %s to %s, status=closed)',
            self::EVENT_NAME, $event->start_date->toDateString(), $event->end_date->toDateString()
        ));

        $center = EvacuationCenter::where('name', self::CENTER_NAME)->first();

        if (! $center) {
            $center = EvacuationCenter::create([
                'barangay_id' => $barangay->id,
                'name' => self::CENTER_NAME,
                'type' => 'school',
                'address' => self::BARANGAY_NAME.', Ligao City, Albay',
                'latitude' => $barangay->centroid_latitude ?? self::CITY_CENTER_LAT,
                'longitude' => $barangay->centroid_longitude ?? self::CITY_CENTER_LNG,
                'status' => 'closed',
            ]);
            $this->command->info('Created evacuation center: '.self::CENTER_NAME);
        } else {
            $this->command->info('Reused existing evacuation center: '.self::CENTER_NAME);
        }

        $insideFamilies = $this->seedFamilyGroup(
            $event, $barangay, self::INSIDE_CENTER_FAMILY_SIZES, 'inside_center', $center, 1
        );
        $outsideFamilies = $this->seedFamilyGroup(
            $event, $barangay, self::OUTSIDE_CENTER_FAMILY_SIZES, 'outside_center', null, $insideFamilies + 1
        );

        $insidePersons = array_sum(self::INSIDE_CENTER_FAMILY_SIZES);
        $outsidePersons = array_sum(self::OUTSIDE_CENTER_FAMILY_SIZES);

        $this->command->info(sprintf(
            'Seeded %s: %d families / %d persons inside %s, %d families / %d persons outside a center. '.
            'Total: %d families / %d persons.',
            self::BARANGAY_NAME, $insideFamilies, $insidePersons, self::CENTER_NAME,
            $outsideFamilies, $outsidePersons, $insideFamilies + $outsideFamilies, $insidePersons + $outsidePersons
        ));
    }

    /**
     * Creates one Family + its Evacuee members + one EvacuationRecord per
     * member, for each size in $familySizes. Names are generic placeholders
     * ("Household N Member M") since the source report has no named
     * individual records -- see class docblock. $startingHouseholdNumber
     * keeps household numbering unique/sequential across the inside and
     * outside groups (1..19, then 20..25) purely for readability; it has
     * no bearing on the real counts, which come from $familySizes.
     */
    private function seedFamilyGroup(
        EvacuationEvent $event,
        Barangay $barangay,
        array $familySizes,
        string $displacementType,
        ?EvacuationCenter $center,
        int $startingHouseholdNumber
    ): int {
        $householdNumber = $startingHouseholdNumber;

        foreach ($familySizes as $memberCount) {
            $family = Family::create([
                'evacuation_event_id' => $event->id,
                'barangay_id' => $barangay->id,
                'is_4ps_beneficiary' => false,
            ]);

            $headOfFamilyId = null;

            for ($m = 1; $m <= $memberCount; $m++) {
                $isHead = $m === 1;
                $sex = rand(0, 1) === 0 ? 'male' : 'female';
                $ageGroup = $this->randomAgeGroup($isHead);

                $evacuee = Evacuee::create([
                    'family_id' => $family->id,
                    'barangay_id' => $barangay->id,
                    'first_name' => "Household {$householdNumber}",
                    'last_name' => "Member {$m}",
                    'sex' => $sex,
                    'date_of_birth' => $this->randomBirthdate($ageGroup),
                    'contact_number' => null,
                    'is_4ps_beneficiary' => false,
                    'status' => 'returned_home',
                ]);

                if ($isHead) {
                    $headOfFamilyId = $evacuee->id;
                }

                EvacuationRecord::create([
                    'evacuee_id' => $evacuee->id,
                    'evacuation_center_id' => $center?->id,
                    'evacuation_event_id' => $event->id,
                    'displacement_type' => $displacementType,
                    'date_in' => $event->start_date,
                    'date_out' => $event->end_date,
                    'status' => 'returned_home',
                ]);
            }

            $family->update(['head_of_family_evacuee_id' => $headOfFamilyId]);
            $householdNumber++;
        }

        return count($familySizes);
    }

    /**
     * Reasonable realistic variety (children/adults/seniors), not a
     * precise reproduction of the report's age brackets -- the report is
     * aggregate-only and doesn't require per-bucket precision here.
     */
    private function randomAgeGroup(bool $isHead): string
    {
        if ($isHead) {
            return rand(1, 100) <= 12 ? 'senior' : 'adult';
        }

        $roll = rand(1, 100);

        return match (true) {
            $roll <= 40 => 'child',
            $roll <= 90 => 'adult',
            default => 'senior',
        };
    }

    private function randomBirthdate(string $ageGroup): string
    {
        return match ($ageGroup) {
            'child' => now()->subYears(rand(1, 17))->subDays(rand(0, 364))->toDateString(),
            'senior' => now()->subYears(rand(60, 85))->subDays(rand(0, 364))->toDateString(),
            default => now()->subYears(rand(18, 59))->subDays(rand(0, 364))->toDateString(),
        };
    }
}
