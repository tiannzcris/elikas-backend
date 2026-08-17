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
 * Demo/sample data for exercising the predictive analytics feature before
 * real PAGASA historical weather data is available for Ligao City.
 *
 * Event names and dates are REAL historical typhoons that affected Albay
 * province. rainfall_mm/max_wind_speed_kph are reasonable but NOT claimed
 * to be precise -- actual PAGASA readings for Ligao City specifically
 * aren't available yet, so these exist to exercise the prediction
 * mechanism itself, not to represent verified historical fact. Every
 * family/evacuee/sample evacuation center created here is entirely
 * fabricated test data -- "[SAMPLE] ..." center names make that
 * unmistakable, and contact numbers only ever use the unallocated 0907
 * prefix so a real "Send Alert" action could never reach an actual person.
 *
 * Safe to re-run: each event is matched by its exact name, and if it
 * already exists the whole block (event + its centers/families/evacuees)
 * is skipped -- running this twice never doubles the data.
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

    public function run(): void
    {
        $barangays = Barangay::all();

        if ($barangays->count() < 8) {
            $this->command->error('Need at least 8 seeded barangays -- run BarangaySeeder first.');

            return;
        }

        // barangay_range/families_per_barangay_range are deliberately scaled
        // to each event's combined rainfall+wind severity -- Durian (highest
        // severity) gets the widest reach and heaviest per-barangay turnout,
        // Leon (lowest) the smallest. Rolly and Kristine have nearly
        // identical combined severity (495) despite a very different
        // rainfall/wind mix, so their ranges are close but not identical
        // (Rolly edges slightly higher). Without this, evacuee counts were
        // independently randomized and gave the regression no real signal
        // to learn from (see commit history -- this produced a severely
        // negative R^2 and a degenerate "1 person" prediction).
        $events = [
            ['name' => 'Typhoon Durian (Reming)', 'start_date' => '2006-11-30', 'end_date' => '2006-12-02', 'rainfall_mm' => 350, 'wind_speed_kph' => 185, 'barangay_range' => [14, 16], 'families_per_barangay_range' => [7, 10]],
            ['name' => 'Typhoon Rolly (Goni)', 'start_date' => '2020-11-01', 'end_date' => '2020-11-02', 'rainfall_mm' => 300, 'wind_speed_kph' => 195, 'barangay_range' => [11, 13], 'families_per_barangay_range' => [6, 9]],
            ['name' => 'Typhoon Kristine (Trami)', 'start_date' => '2024-10-22', 'end_date' => '2024-10-25', 'rainfall_mm' => 400, 'wind_speed_kph' => 95, 'barangay_range' => [10, 12], 'families_per_barangay_range' => [5, 8]],
            ['name' => 'Typhoon Leon (Kong-rey)', 'start_date' => '2024-10-29', 'end_date' => '2024-10-30', 'rainfall_mm' => 120, 'wind_speed_kph' => 75, 'barangay_range' => [5, 7], 'families_per_barangay_range' => [2, 4]],
            ['name' => 'Tropical Storm Mitag (Mirasol)', 'start_date' => '2025-09-16', 'end_date' => '2025-09-17', 'rainfall_mm' => 150, 'wind_speed_kph' => 65, 'barangay_range' => [6, 8], 'families_per_barangay_range' => [3, 5]],
        ];

        $totals = ['events' => 0, 'centers' => 0, 'families' => 0, 'evacuees' => 0];

        foreach ($events as $eventData) {
            if (EvacuationEvent::where('name', $eventData['name'])->exists()) {
                $this->command->info("Skipped (already exists): {$eventData['name']}");

                continue;
            }

            $event = EvacuationEvent::create([
                'name' => $eventData['name'],
                'event_type' => 'typhoon',
                'typhoon_category' => str_starts_with($eventData['name'], 'Tropical Storm') ? 'Tropical Storm' : 'Typhoon',
                'max_wind_speed_kph' => $eventData['wind_speed_kph'],
                'rainfall_mm' => $eventData['rainfall_mm'],
                'start_date' => $eventData['start_date'],
                'end_date' => $eventData['end_date'],
                'status' => 'closed',
                'description' => 'Demo/sample data for testing predictive analytics. This is a real '.
                    'historical typhoon that affected Albay province -- the rainfall/wind figures are '.
                    'reasonable approximations for exercising the forecast mechanism, not precise '.
                    'PAGASA-verified readings for Ligao City specifically.',
            ]);
            $totals['events']++;
            $this->command->info("Created event: {$eventData['name']}");

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
