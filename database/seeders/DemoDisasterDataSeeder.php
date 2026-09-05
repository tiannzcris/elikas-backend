<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\AnalyticsPrediction;
use App\Models\Barangay;
use App\Models\Evacuee;
use App\Models\EvacuationCenter;
use App\Models\EvacuationCenterFacility;
use App\Models\EvacuationEvent;
use App\Models\EvacuationRecord;
use App\Models\Family;
use App\Models\ReliefDistribution;
use App\Models\ReliefItem;
use App\Models\User;
use App\Models\WeatherReading;
use Illuminate\Database\Seeder;

/**
 * Demo/sample data for exercising the predictive analytics feature.
 *
 * Event names and dates are REAL historical typhoons confirmed to have
 * significantly affected Albay, all falling within the 2020-2024 window
 * covered by the real imported PAGASA Legazpi station daily readings
 * (database/samples/Legazpi_Daily_Data.csv, imported via `weather:import`).
 * rainfall_mm/max_wind_speed_kph are NOT estimates -- they're computed
 * directly from that real data for each event's exact date range
 * (SUM of daily rainfall = total accumulated rainfall; MAX of daily wind
 * speed = peak recorded wind) via computeRealSeverity() below.
 *
 * IMPORTANT, confirmed by inspecting the real data directly: this
 * station's WIND_SPEED column is a daily MEAN synoptic observation, not a
 * storm-peak/gust reading -- it ranges only 0-9 m/s (0-32.4 kph) across
 * the entire 5-year file, nowhere close to these typhoons' widely-reported
 * 150+ kph sustained winds at landfall. The computed max_wind_speed_kph
 * values below are genuinely real and correctly computed from this
 * station's actual data, but they will look surprisingly low compared to
 * news reports -- that's an honest property of what this data source
 * measures, not a computation error.
 *
 * Family/evacuee/sample-center data remains necessarily fabricated (no
 * real digitized evacuee records exist for these events in Ligao
 * specifically) -- realistic Filipino names, 0907 phone prefix, and
 * "[SAMPLE] ..." center names make that unmistakable, same as before.
 *
 * Self-healing on every run: unconditionally wipes the OLD 5
 * estimated-value events (by their old fixed names -- see
 * OLD_EVENT_NAMES_TO_WIPE) before creating the new 5, so a stale
 * estimated-value event can never linger under the same name as one of
 * the new real-value ones. After that, each new event is matched by its
 * own exact name and skipped if it already exists -- re-running this
 * twice never doubles the new data.
 *
 * NOT part of the default `php artisan db:seed` chain (see
 * DatabaseSeeder) -- this is demo/testing data, not something a real
 * deployment should get automatically. Run explicitly:
 *   php artisan db:seed --class=DemoDisasterDataSeeder
 */
class DemoDisasterDataSeeder extends Seeder
{
    private const CITY_CENTER_LAT = 13.1391;

    private const CITY_CENTER_LNG = 123.5321;

    // The only station real weather data has been imported for so far.
    private const STATION = 'Legazpi';

    // Exact names DemoDisasterDataSeeder used to create with ESTIMATED
    // rainfall/wind figures -- wiped unconditionally on every run so they
    // can never coexist with (or block creation of) the new real-value
    // events below, even where a name is reused (Rolly/Kristine/Leon).
    private const OLD_EVENT_NAMES_TO_WIPE = [
        'Typhoon Durian (Reming)',
        'Typhoon Rolly (Goni)',
        'Typhoon Kristine (Trami)',
        'Typhoon Leon (Kong-rey)',
        'Tropical Storm Mitag (Mirasol)',
    ];

    private const FIRST_NAMES_MALE = [
        'Jose', 'Juan', 'Antonio', 'Pedro', 'Ricardo', 'Eduardo', 'Ramon', 'Rodrigo',
        'Ernesto', 'Rogelio', 'Danilo', 'Renato', 'Roberto', 'Fernando', 'Manuel',
        'Alfredo', 'Arnel', 'Bonifacio', 'Cesar', 'Domingo', 'Efren', 'Gerardo',
        'Herminio', 'Isagani', 'Leonardo', 'Marcelo', 'Nestor', 'Oscar', 'Pablo', 'Reynaldo',
    ];

    private const FIRST_NAMES_FEMALE = [
        'Maria', 'Carmen', 'Rosario', 'Teresita', 'Corazon', 'Josefina', 'Remedios',
        'Luz', 'Erlinda', 'Perla', 'Gloria', 'Imelda', 'Elena', 'Cristina', 'Susana',
        'Victoria', 'Angelina', 'Beatriz', 'Concepcion', 'Dolores', 'Estela',
        'Flordeliza', 'Gemma', 'Herminia', 'Isabel', 'Juanita', 'Leticia', 'Milagros',
        'Norma', 'Ofelia',
    ];

    private const LAST_NAMES = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo', 'Garcia', 'Mendoza', 'Torres',
        'Andrada', 'Fernandez', 'Villanueva', 'Gonzales', 'Ramos', 'Aquino', 'Domingo',
        'Castillo', 'Delos Santos', 'Del Rosario', 'Aguilar', 'Marquez', 'Navarro',
        'Pascual', 'Salazar', 'Tolentino', 'Villareal',
    ];

    private const PWD_TYPES = ['visual', 'mobility', 'hearing', 'intellectual', 'psychosocial'];

    // Matches evacuation_center_facilities.facility_type's enum exactly (19
    // fixed types) -- verified against the migration, not assumed.
    private const FACILITY_TYPES = [
        'latrine_compost_pit', 'latrine_sealed',
        'toilet_male', 'toilet_female', 'toilet_common',
        'bathing_area_male', 'bathing_area_female', 'bathing_area_common',
        'handwashing_facility', 'laundry_space',
        'women_friendly_space', 'child_friendly_space',
        'health_facility', 'prayer_room', 'community_kitchen',
        'livestock_area', 'camp_management_desk', 'info_board', 'storage_area',
    ];

    private const FACILITY_CONCERN_NOTES = [
        'Needs repair', 'Awaiting replacement parts', 'Temporarily out of supplies',
        'Reported damaged during last use', 'Pending maintenance request',
    ];

    public function run(): void
    {
        $barangays = Barangay::all();

        if ($barangays->count() < 8) {
            $this->command->error('Need at least 8 seeded barangays -- run BarangaySeeder first.');

            return;
        }

        $this->wipeOldEstimatedEvents();

        // barangay_range/families_per_barangay_range are scaled to each
        // event's REAL computed combined rainfall+wind severity (see
        // computeRealSeverity() below), not an estimate -- ranked by
        // combined severity, highest to lowest: Kristine (real accumulated
        // rainfall 483.9mm over its 4-day window is by far the highest of
        // the 5) > Quinta > Rolly (close to each other) > Bising > Leon
        // (barely any rain in the real data, 2mm total -- a genuinely mild
        // event, nothing like the others). This real ranking is very
        // different from the old estimated-value ordering, since real
        // daily-mean wind speed barely varies across all 5 (see the class
        // docblock) -- rainfall totals now do almost all of the
        // differentiating work.
        $events = [
            ['name' => 'Typhoon Quinta (Molave)', 'start_date' => '2020-10-24', 'end_date' => '2020-10-26', 'barangay_range' => [11, 13], 'families_per_barangay_range' => [6, 9]],
            ['name' => 'Typhoon Rolly (Goni)', 'start_date' => '2020-10-31', 'end_date' => '2020-11-02', 'barangay_range' => [10, 12], 'families_per_barangay_range' => [5, 8]],
            ['name' => 'Typhoon Bising (Surigae)', 'start_date' => '2021-04-18', 'end_date' => '2021-04-20', 'barangay_range' => [7, 9], 'families_per_barangay_range' => [3, 5]],
            ['name' => 'Typhoon Kristine (Trami)', 'start_date' => '2024-10-22', 'end_date' => '2024-10-25', 'barangay_range' => [14, 16], 'families_per_barangay_range' => [7, 10]],
            ['name' => 'Typhoon Leon (Kong-rey)', 'start_date' => '2024-10-29', 'end_date' => '2024-10-30', 'barangay_range' => [5, 6], 'families_per_barangay_range' => [2, 3]],
        ];

        $totals = ['events' => 0, 'centers' => 0, 'families' => 0, 'evacuees' => 0];
        $createdEventNames = [];

        foreach ($events as $eventData) {
            if (EvacuationEvent::where('name', $eventData['name'])->exists()) {
                $this->command->info("Skipped (already exists): {$eventData['name']}");
                $createdEventNames[] = $eventData['name'];

                continue;
            }

            $severity = $this->computeRealSeverity($eventData['start_date'], $eventData['end_date']);

            if ($severity === null) {
                $this->command->error(
                    "No weather_readings found for {$eventData['name']} ({$eventData['start_date']} to ".
                    "{$eventData['end_date']}, station=".self::STATION.") -- skipping this event entirely. ".
                    'Import the real PAGASA data first (php artisan weather:import ... --station='.self::STATION.').'
                );

                continue;
            }

            $event = EvacuationEvent::create([
                'name' => $eventData['name'],
                'event_type' => 'typhoon',
                'typhoon_category' => str_starts_with($eventData['name'], 'Tropical Storm') ? 'Tropical Storm' : 'Typhoon',
                'max_wind_speed_kph' => $severity['wind_speed_kph'],
                'rainfall_mm' => $severity['rainfall_mm'],
                'start_date' => $eventData['start_date'],
                'end_date' => $eventData['end_date'],
                'status' => 'closed',
                'description' => sprintf(
                    'Demo/sample data for testing predictive analytics. This is a real historical typhoon '.
                    'that significantly affected Albay province. rainfall_mm (%.2f) is the SUM of real daily '.
                    'PAGASA %s station readings across this event\'s date range; max_wind_speed_kph (%.2f) is '.
                    'the MAX daily reading across the same range (%d day(s) of real data) -- both computed '.
                    'from actually imported weather_readings, not estimated. Family/evacuee data below this '.
                    'event is still fabricated demo data -- no real digitized evacuee records exist for this '.
                    'event in Ligao specifically.',
                    $severity['rainfall_mm'], self::STATION, $severity['wind_speed_kph'], $severity['days_found']
                ),
            ]);
            $totals['events']++;
            $createdEventNames[] = $eventData['name'];
            $this->command->info(sprintf(
                'Created event: %s (rainfall_mm=%.2f, max_wind_speed_kph=%.2f, from %d real day(s) of %s data)',
                $eventData['name'], $severity['rainfall_mm'], $severity['wind_speed_kph'], $severity['days_found'], self::STATION
            ));

            [$barangayMin, $barangayMax] = $eventData['barangay_range'];
            [$familyMin, $familyMax] = $eventData['families_per_barangay_range'];

            $barangayCount = min($barangays->count(), rand($barangayMin, $barangayMax));
            foreach ($barangays->random($barangayCount) as $barangay) {
                $center = $this->resolveCenterFor($barangay, $totals);

                $familyCount = rand($familyMin, $familyMax);
                for ($i = 0; $i < $familyCount; $i++) {
                    $totals['evacuees'] += $this->createFamily($event, $barangay, $center);
                    $totals['families']++;
                }
            }
        }

        $this->command->info(sprintf(
            'Done. Events created: %d, sample centers created: %d, families created: %d, evacuees created: %d.',
            $totals['events'], $totals['centers'], $totals['families'], $totals['evacuees']
        ));

        $this->seedResourceCosts($createdEventNames);
        $this->seedSampleFacilities();
    }

    /**
     * Unconditionally removes the OLD 5 estimated-value events (matched by
     * their exact old names) and everything that hangs off them, every
     * time this seeder runs -- so a stale estimated-value row can never
     * survive under (or block re-creation of) a name reused by the new
     * real-value set (Rolly/Kristine/Leon keep their names; Quinta/Bising
     * replace Durian/Mitag). families/evacuees/evacuation_records/
     * prediction_datasets/relief_distributions all cascade automatically
     * on delete (see the evacuation_events migration); alerts and
     * analytics_predictions use nullOnDelete instead of cascading, so
     * those are cleaned up explicitly first. weather_readings and sample
     * evacuation centers are never touched here.
     */
    private function wipeOldEstimatedEvents(): void
    {
        $eventIds = EvacuationEvent::whereIn('name', self::OLD_EVENT_NAMES_TO_WIPE)->pluck('id');

        if ($eventIds->isEmpty()) {
            return;
        }

        Alert::whereIn('evacuation_event_id', $eventIds)->delete();
        AnalyticsPrediction::whereIn('evacuation_event_id', $eventIds)->delete();
        EvacuationEvent::whereIn('id', $eventIds)->delete();

        $this->command->info('Wiped '.$eventIds->count().' old estimated-value historical event(s) and their dependent data.');
    }

    /**
     * Real severity for one event's date range, computed directly from
     * weather_readings -- SUM of rainfall_mm (total accumulated rainfall)
     * and MAX of wind_speed_kph (peak recorded reading), both of which
     * naturally skip NULL/missing days via plain SQL aggregate semantics
     * (no manual filtering needed). Returns null if there's no real data
     * at all for this range yet, so the caller can skip the event rather
     * than silently creating one with a false "0mm rainfall" reading.
     */
    private function computeRealSeverity(string $startDate, string $endDate): ?array
    {
        $query = WeatherReading::whereBetween('reading_date', [$startDate, $endDate])
            ->where('station', self::STATION);

        $daysFound = (clone $query)->count();

        if ($daysFound === 0) {
            return null;
        }

        return [
            'rainfall_mm' => round((float) (clone $query)->sum('rainfall_mm'), 2),
            'wind_speed_kph' => round((float) (clone $query)->max('wind_speed_kph'), 2),
            'days_found' => $daysFound,
        ];
    }

    /**
     * Gives each "[SAMPLE] ..." evacuation center a realistic (not
     * exhaustive) facilities checklist, so its detail page isn't
     * completely empty. Deliberately varies coverage per center --
     * 8-14 of the 19 possible types, ~90% marked available -- rather
     * than every center having all 19 fully stocked, since real sites
     * genuinely don't. Idempotent per center: a center already carrying
     * any facility rows is left alone.
     */
    private function seedSampleFacilities(): void
    {
        $sampleCenters = EvacuationCenter::where('name', 'like', '[SAMPLE]%')->get();

        foreach ($sampleCenters as $center) {
            if ($center->facilities()->exists()) {
                $this->command->info("Skipped facilities (already exist): {$center->name}");

                continue;
            }

            $types = collect(self::FACILITY_TYPES)->shuffle()->take(rand(8, 14));

            foreach ($types as $facilityType) {
                $isAvailable = rand(1, 100) <= 90;

                EvacuationCenterFacility::create([
                    'evacuation_center_id' => $center->id,
                    'facility_type' => $facilityType,
                    'quantity' => rand(1, 5),
                    'is_available' => $isAvailable,
                    'concerns_and_needs' => (! $isAvailable && rand(1, 100) <= 60)
                        ? self::FACILITY_CONCERN_NOTES[array_rand(self::FACILITY_CONCERN_NOTES)]
                        : null,
                    'recorded_at' => now(),
                ]);
            }

            $this->command->info("Seeded {$types->count()} facility records for {$center->name}");
        }
    }

    /**
     * Gives PredictiveAnalyticsService's historicalAverageCostPerPerson()
     * real (non-zero) data to average -- without this, "Estimated resource
     * cost" on Predictive Analytics is accurately ₱0 (no relief/cash data
     * exists at all) but unhelpfully blank for demo purposes. One
     * ReliefDistribution row per event: quantity = that event's actual
     * evacuee count (one food pack per displaced person), unit_cost = a
     * per-event randomized ₱400-800 cost-per-person -- so total_cost
     * (a MySQL generated column, quantity * unit_cost) scales with how many
     * people that event actually displaced, not a flat number reused
     * across all 5. Idempotent: skipped per-event if relief data already
     * exists for it.
     */
    private function seedResourceCosts(array $eventNames): void
    {
        $reliefItem = ReliefItem::where('item_name', 'Family Food Pack (FFP)')->first();
        $distributedBy = User::first();

        if (! $reliefItem || ! $distributedBy) {
            $this->command->error('Need a seeded relief item and at least one user -- run migrations/AdminUserSeeder first. Skipping resource cost seeding.');

            return;
        }

        foreach (EvacuationEvent::whereIn('name', $eventNames)->get() as $event) {
            if (ReliefDistribution::where('evacuation_event_id', $event->id)->exists()) {
                $this->command->info("Skipped resource cost (already exists): {$event->name}");

                continue;
            }

            $evacueeCount = EvacuationRecord::where('evacuation_event_id', $event->id)
                ->distinct('evacuee_id')->count('evacuee_id');

            if ($evacueeCount === 0) {
                $this->command->warn("No evacuees found for {$event->name} -- skipping resource cost.");

                continue;
            }

            $costPerPerson = rand(400, 800);

            ReliefDistribution::create([
                'evacuation_event_id' => $event->id,
                'evacuation_center_id' => null,
                'relief_item_id' => $reliefItem->id,
                'source' => 'lgu',
                'quantity' => $evacueeCount,
                'unit_cost' => $costPerPerson,
                'distributed_by' => $distributedBy->id,
                'distributed_at' => $event->start_date,
            ]);

            $this->command->info(sprintf(
                'Seeded resource cost for %s: %d evacuees x P%d/person = P%s total.',
                $event->name, $evacueeCount, $costPerPerson, number_format($evacueeCount * $costPerPerson, 2)
            ));
        }
    }

    /**
     * Reuses a barangay's real evacuation center if one already exists;
     * otherwise reuses (or creates) its "[SAMPLE] ..." placeholder --
     * checked by name so re-running this seeder never creates a second one.
     */
    private function resolveCenterFor(Barangay $barangay, array &$totals): EvacuationCenter
    {
        $existingReal = $barangay->evacuationCenters()->first();
        if ($existingReal) {
            return $existingReal;
        }

        $sampleName = "[SAMPLE] {$barangay->name} Evacuation Center";
        $existingSample = EvacuationCenter::where('name', $sampleName)->first();
        if ($existingSample) {
            return $existingSample;
        }

        $capacityPersons = rand(100, 300);

        $center = EvacuationCenter::create([
            'barangay_id' => $barangay->id,
            'name' => $sampleName,
            'type' => 'other',
            'address' => "{$barangay->name}, Ligao City, Albay (sample/placeholder location, not a verified address)",
            'latitude' => self::CITY_CENTER_LAT + $this->smallOffset(),
            'longitude' => self::CITY_CENTER_LNG + $this->smallOffset(),
            'capacity_persons' => $capacityPersons,
            'capacity_families' => (int) round($capacityPersons / 4),
            'status' => 'active',
        ]);
        $totals['centers']++;

        return $center;
    }

    /**
     * +/- ~0.01 degrees (roughly 1km) so sample centers land at slightly
     * different points on the map instead of stacking on one spot.
     */
    private function smallOffset(): float
    {
        return rand(-100, 100) / 10000;
    }

    /**
     * Historical/closed event -- these evacuees returned home long ago, so
     * both the record and the evacuee itself are created already checked
     * out. Leaving them "currently_evacuated" would wrongly inflate
     * today's live displaced-persons counts with data from 2006-2025.
     */
    private function createFamily(EvacuationEvent $event, Barangay $barangay, EvacuationCenter $center): int
    {
        $isFourPsHousehold = rand(1, 100) <= 15;

        $family = Family::create([
            'evacuation_event_id' => $event->id,
            'barangay_id' => $barangay->id,
            'is_4ps_beneficiary' => $isFourPsHousehold,
        ]);

        $memberCount = rand(2, 5);
        $headOfFamilyId = null;

        for ($m = 0; $m < $memberCount; $m++) {
            $isHead = $m === 0;
            $sex = rand(0, 1) === 0 ? 'male' : 'female';
            $ageGroup = $this->randomAgeGroup($isHead);
            $dob = $this->randomBirthdate($ageGroup);

            $isPwd = rand(1, 100) <= 12;
            $isChildbearingAdult = $sex === 'female' && $ageGroup === 'adult';

            $evacuee = Evacuee::create([
                'family_id' => $family->id,
                'barangay_id' => $barangay->id,
                'first_name' => $sex === 'male'
                    ? self::FIRST_NAMES_MALE[array_rand(self::FIRST_NAMES_MALE)]
                    : self::FIRST_NAMES_FEMALE[array_rand(self::FIRST_NAMES_FEMALE)],
                'last_name' => self::LAST_NAMES[array_rand(self::LAST_NAMES)],
                'sex' => $sex,
                'date_of_birth' => $dob,
                'contact_number' => '0907'.str_pad((string) rand(0, 9999999), 7, '0', STR_PAD_LEFT),
                'is_pwd' => $isPwd,
                'pwd_type' => $isPwd ? self::PWD_TYPES[array_rand(self::PWD_TYPES)] : null,
                'is_pregnant' => $isChildbearingAdult && rand(1, 100) <= 10,
                'is_lactating' => $isChildbearingAdult && rand(1, 100) <= 10,
                'is_solo_parent' => $ageGroup !== 'child' && rand(1, 100) <= 12,
                'is_indigenous_person' => rand(1, 100) <= 8,
                'is_4ps_beneficiary' => $isFourPsHousehold,
                'status' => 'returned_home',
            ]);

            if ($isHead) {
                $headOfFamilyId = $evacuee->id;
            }

            EvacuationRecord::create([
                'evacuee_id' => $evacuee->id,
                'evacuation_center_id' => $center->id,
                'evacuation_event_id' => $event->id,
                'displacement_type' => 'inside_center',
                'date_in' => $event->start_date,
                'date_out' => $event->end_date,
                'status' => 'returned_home',
            ]);
        }

        $family->update(['head_of_family_evacuee_id' => $headOfFamilyId]);

        return $memberCount;
    }

    /**
     * The head of family is (almost) always an adult, occasionally a
     * senior citizen. Other members are a realistic household mix of
     * children, adults, and the occasional senior.
     */
    private function randomAgeGroup(bool $isHead): string
    {
        if ($isHead) {
            return rand(1, 100) <= 12 ? 'senior' : 'adult';
        }

        $roll = rand(1, 100);

        return match (true) {
            $roll <= 45 => 'child',
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
