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
 *
 * rainfall_mm is NOT an estimate -- it's computed directly from that real
 * data for each event's exact date range (SUM of daily rainfall = total
 * accumulated rainfall) via computeRealSeverity() below.
 *
 * max_wind_speed_kph is NOT computed from the station data. This station's
 * WIND_SPEED column is a daily MEAN synoptic observation, not a
 * storm-peak/gust reading -- it ranges only 0-9 m/s (0-32.4 kph) across the
 * entire 5-year file, so it barely varied across these 5 storms and carried
 * almost no real signal about actual storm intensity. Instead,
 * max_wind_speed_kph uses documented PAGASA Severe Weather Bulletin figures
 * for wind actually experienced in Albay at each storm's closest approach
 * (see DOCUMENTED_WIND_KPH) -- not each storm's national peak intensity,
 * since several of these tracks passed well outside Albay while Albay
 * itself sat under a lower PAGASA wind signal (Bising, Kristine, Leon).
 * These are cited, documented figures, not guesses.
 *
 * Family/evacuee/sample-center data remains necessarily fabricated (no
 * real digitized evacuee records exist for these events in Ligao
 * specifically) -- realistic Filipino names, 0907 phone prefix, and an
 * is_seeded = true flag on every center it creates (see resolveCenterFor())
 * make that unmistakable. This replaces the old "[SAMPLE] " name prefix,
 * which was purely cosmetic and had no real queryable flag behind it.
 *
 * Self-healing on every run: unconditionally wipes all 5 current events
 * (plus 2 legacy-only names from an earlier version of this seeder -- see
 * EVENT_NAMES_TO_WIPE) before recreating them fresh, so a stale row from
 * an earlier revision of this file's severity figures can never linger.
 * This means family/evacuee/resource-cost data for these 5 events is
 * regenerated on every run, not preserved across revisions -- deliberate,
 * since this is demo data meant to always reflect the current formulas.
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

    // Wiped unconditionally on every run, then recreated fresh in the loop
    // below -- covers both the 2 names only ever used by the very first
    // (fully-estimated) version of this seeder (Durian, Mitag) AND the
    // current 5 event names (Quinta/Rolly/Bising/Kristine/Leon), so a
    // revision to DOCUMENTED_WIND_KPH or the severity ranges always takes
    // effect, never blocked by a stale existing row under the same name.
    private const EVENT_NAMES_TO_WIPE = [
        'Typhoon Durian (Reming)',
        'Tropical Storm Mitag (Mirasol)',
        'Typhoon Quinta (Molave)',
        'Typhoon Rolly (Goni)',
        'Typhoon Bising (Surigae)',
        'Typhoon Kristine (Trami)',
        'Typhoon Leon (Kong-rey)',
    ];

    // Documented wind actually experienced in Albay for each event, from
    // PAGASA Severe Weather Bulletins at closest approach/landfall -- see
    // the class docblock for why this replaces the raw station reading.
    private const DOCUMENTED_WIND_KPH = [
        'Typhoon Quinta (Molave)' => 130.0,    // PAGASA bulletin at landfall, Tabaco City, Albay
        'Typhoon Rolly (Goni)' => 225.0,       // PAGASA bulletin at landfall, Tiwi, Albay
        'Typhoon Bising (Surigae)' => 100.0,   // core passed offshore/north; Albay only under Signal No. 2
        'Typhoon Kristine (Trami)' => 60.0,    // rainfall-dominant for Albay; Albay only under Signal No. 1
        'Typhoon Leon (Kong-rey)' => 55.0,     // track toward Batanes/Cagayan Valley; Albay only under Signal No. 1
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

    // Realistic variety for a newly-seeded center's name/type -- matches
    // evacuation_centers.type's actual enum values (verified against the
    // migration, not assumed). Picked randomly per new center instead of
    // every seeded center being an identically-named/typed "Evacuation
    // Center", now that the "[SAMPLE] " prefix is gone.
    private const CENTER_TYPE_OPTIONS = [
        ['type' => 'school', 'suffix' => 'Elementary School'],
        ['type' => 'school', 'suffix' => 'National High School'],
        ['type' => 'barangay_hall', 'suffix' => 'Barangay Hall'],
        ['type' => 'covered_court', 'suffix' => 'Covered Court'],
        ['type' => 'gymnasium', 'suffix' => 'Multipurpose Gymnasium'],
        ['type' => 'church', 'suffix' => 'Parish Chapel'],
    ];

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

        $this->wipeExistingEvents();

        // barangay_range/families_per_barangay_range are scaled to each
        // event's combined severity -- REAL computed rainfall_mm (SUM from
        // weather_readings) plus DOCUMENTED_WIND_KPH (PAGASA bulletin
        // figures for Albay specifically, see class docblock) -- ranked
        // highest to lowest: Rolly (extreme wind 225kph + high rain
        // 278.5mm) > Quinta (high wind 130kph + high rain 300.8mm) >
        // Kristine (extreme rain 483.9mm, but only 60kph -- rainfall-
        // dominant, not wind-dominant, for Albay) > Bising (moderate both,
        // 115.25mm/100kph) > Leon (mildest: 2mm rain, 55kph).
        $events = [
            ['name' => 'Typhoon Rolly (Goni)', 'start_date' => '2020-10-31', 'end_date' => '2020-11-02', 'barangay_range' => [16, 18], 'families_per_barangay_range' => [9, 12]],
            ['name' => 'Typhoon Quinta (Molave)', 'start_date' => '2020-10-24', 'end_date' => '2020-10-26', 'barangay_range' => [13, 15], 'families_per_barangay_range' => [7, 10]],
            ['name' => 'Typhoon Kristine (Trami)', 'start_date' => '2024-10-22', 'end_date' => '2024-10-25', 'barangay_range' => [10, 12], 'families_per_barangay_range' => [5, 8]],
            ['name' => 'Typhoon Bising (Surigae)', 'start_date' => '2021-04-18', 'end_date' => '2021-04-20', 'barangay_range' => [7, 9], 'families_per_barangay_range' => [3, 5]],
            ['name' => 'Typhoon Leon (Kong-rey)', 'start_date' => '2024-10-29', 'end_date' => '2024-10-30', 'barangay_range' => [4, 5], 'families_per_barangay_range' => [2, 3]],
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

            $windSpeedKph = self::DOCUMENTED_WIND_KPH[$eventData['name']];

            $event = EvacuationEvent::create([
                'name' => $eventData['name'],
                'event_type' => 'typhoon',
                'typhoon_category' => str_starts_with($eventData['name'], 'Tropical Storm') ? 'Tropical Storm' : 'Typhoon',
                'max_wind_speed_kph' => $windSpeedKph,
                'rainfall_mm' => $severity['rainfall_mm'],
                'start_date' => $eventData['start_date'],
                'end_date' => $eventData['end_date'],
                'status' => 'closed',
                'description' => sprintf(
                    'Demo/sample data for testing predictive analytics. This is a real historical typhoon '.
                    'that significantly affected Albay province. rainfall_mm (%.2f) is the SUM of real daily '.
                    'PAGASA %s station readings across this event\'s date range (%d day(s) of real data). '.
                    'max_wind_speed_kph (%.2f) is a documented PAGASA Severe Weather Bulletin figure for '.
                    'wind actually experienced in Albay at this storm\'s closest approach, not the raw '.
                    'station reading (too uniform to be a useful signal) and not necessarily the storm\'s '.
                    'national peak intensity. Family/evacuee data below this event is still fabricated demo '.
                    'data -- no real digitized evacuee records exist for this event in Ligao specifically.',
                    $severity['rainfall_mm'], self::STATION, $severity['days_found'], $windSpeedKph
                ),
            ]);
            $totals['events']++;
            $createdEventNames[] = $eventData['name'];
            $this->command->info(sprintf(
                'Created event: %s (rainfall_mm=%.2f [real SUM, %d day(s)], max_wind_speed_kph=%.2f [documented PAGASA bulletin])',
                $eventData['name'], $severity['rainfall_mm'], $severity['days_found'], $windSpeedKph
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
     * Unconditionally removes all 5 current events (plus 2 legacy-only
     * names, see EVENT_NAMES_TO_WIPE) and everything that hangs off them,
     * every time this seeder runs -- so a stale row from an earlier
     * revision of this file's severity figures (rainfall ranges, documented
     * wind values, barangay/family scaling) can never survive a re-run
     * under the same name. families/evacuees/evacuation_records/
     * prediction_datasets/relief_distributions all cascade automatically
     * on delete (see the evacuation_events migration); alerts and
     * analytics_predictions use nullOnDelete instead of cascading, so
     * those are cleaned up explicitly first. weather_readings and sample
     * evacuation centers are never touched here.
     */
    private function wipeExistingEvents(): void
    {
        $eventIds = EvacuationEvent::whereIn('name', self::EVENT_NAMES_TO_WIPE)->pluck('id');

        if ($eventIds->isEmpty()) {
            return;
        }

        Alert::whereIn('evacuation_event_id', $eventIds)->delete();
        AnalyticsPrediction::whereIn('evacuation_event_id', $eventIds)->delete();
        EvacuationEvent::whereIn('id', $eventIds)->delete();

        $this->command->info('Wiped '.$eventIds->count().' existing historical event(s) and their dependent data.');
    }

    /**
     * Real rainfall for one event's date range, computed directly from
     * weather_readings -- SUM of rainfall_mm (total accumulated rainfall),
     * which naturally skips NULL/missing days via plain SQL aggregate
     * semantics (no manual filtering needed). Returns null if there's no
     * real data at all for this range yet, so the caller can skip the
     * event rather than silently creating one with a false "0mm rainfall"
     * reading. Wind is NOT computed here -- see DOCUMENTED_WIND_KPH and
     * the class docblock for why documented bulletin figures are used
     * instead of this station's raw (too-uniform) wind reading.
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
            'days_found' => $daysFound,
        ];
    }

    /**
     * Gives each seeded (is_seeded = true) evacuation center a realistic
     * (not exhaustive) facilities checklist, so its detail page isn't
     * completely empty. Deliberately varies coverage per center --
     * 8-14 of the 19 possible types, ~90% marked available -- rather
     * than every center having all 19 fully stocked, since real sites
     * genuinely don't. Idempotent per center: a center already carrying
     * any facility rows is left alone.
     */
    private function seedSampleFacilities(): void
    {
        $sampleCenters = EvacuationCenter::where('is_seeded', true)->get();

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
     * Reuses a barangay's real (non-seeded) evacuation center if one
     * already exists; otherwise reuses (or creates) its is_seeded=true
     * placeholder -- matched via the flag, not name, since a seeded
     * center's name is now randomly varied (see CENTER_TYPE_OPTIONS)
     * rather than a predictable "[SAMPLE] ..." string. This also matches
     * against centers DemoActiveEventSeeder already created for the same
     * barangay, since both seeders use the same is_seeded flag.
     */
    private function resolveCenterFor(Barangay $barangay, array &$totals): EvacuationCenter
    {
        $existingReal = $barangay->evacuationCenters()
            ->where(fn ($q) => $q->where('is_seeded', false)->orWhereNull('is_seeded'))
            ->first();
        if ($existingReal) {
            return $existingReal;
        }

        $existingSeeded = $barangay->evacuationCenters()->where('is_seeded', true)->first();
        if ($existingSeeded) {
            return $existingSeeded;
        }

        $choice = self::CENTER_TYPE_OPTIONS[array_rand(self::CENTER_TYPE_OPTIONS)];
        $capacityPersons = rand(100, 300);

        $center = EvacuationCenter::create([
            'barangay_id' => $barangay->id,
            'name' => "{$barangay->name} {$choice['suffix']}",
            'type' => $choice['type'],
            'address' => "{$barangay->name}, Ligao City, Albay (sample/placeholder location, not a verified address)",
            'latitude' => self::CITY_CENTER_LAT + $this->smallOffset(),
            'longitude' => self::CITY_CENTER_LNG + $this->smallOffset(),
            'capacity_persons' => $capacityPersons,
            'capacity_families' => (int) round($capacityPersons / 4),
            'status' => 'active',
            'is_seeded' => true,
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
