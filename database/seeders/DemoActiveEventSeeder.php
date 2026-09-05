<?php

namespace Database\Seeders;

use App\Models\Alert;
use App\Models\AnalyticsPrediction;
use App\Models\Barangay;
use App\Models\Evacuee;
use App\Models\EvacuationCenter;
use App\Models\EvacuationEvent;
use App\Models\EvacuationRecord;
use App\Models\Family;
use Illuminate\Database\Seeder;

/**
 * Quickly populates ONE realistic, CURRENTLY ACTIVE disaster scenario for a
 * live demo or defense -- entirely separate from DemoDisasterDataSeeder,
 * which only creates CLOSED historical events for predictive analytics
 * training data. This seeder never reads, modifies, or deletes anything
 * from that seeder's 5 historical events; the only rows it ever touches are
 * ones matching this seeder's own 4 fixed variant names (see VARIANTS).
 *
 * Every family/evacuee/sample evacuation center created here is entirely
 * fabricated test data -- flagged is_seeded = true (see resolveCenterFor())
 * rather than a "[SAMPLE] ..." name prefix, which was purely cosmetic and
 * had no real queryable flag behind it. Contact numbers only ever use the
 * unallocated 0907 prefix so a real "Send Alert" action could never reach
 * an actual person.
 *
 * Four selectable variants (different name/scale each time, so repeated
 * demo runs don't look identical to anyone who saw an earlier one).
 * Artisan's db:seed command rejects any option it doesn't itself define
 * (confirmed directly -- "The '--variant' option does not exist"), so the
 * variant is selected via an environment variable instead, keeping the
 * same `db:seed --class=...` invocation:
 *
 *   DEMO_VARIANT=1 php artisan db:seed --class=DemoActiveEventSeeder
 *
 * Safely switchable: running a different variant automatically removes
 * whichever of the 4 variant events currently exists (plus its
 * families/evacuees/evacuation records/predictions) before seeding the
 * newly selected one -- never leaves two variants' data sitting side by
 * side, and never touches the historical closed events or real data,
 * since the cleanup only ever matches these 4 exact fixed names.
 *
 * NOT part of the default `php artisan db:seed` chain (see
 * DatabaseSeeder) -- run explicitly, as shown above.
 */
class DemoActiveEventSeeder extends Seeder
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

    // Realistic variety for a newly-seeded center's name/type -- matches
    // evacuation_center_facilities' actual 'type' enum values (verified
    // against the migration, not assumed). Picked randomly per new center
    // instead of every seeded center being an identically-named/typed
    // "Evacuation Center", now that the "[SAMPLE] " prefix (which used to
    // make every one look the same anyway) is gone.
    private const CENTER_TYPE_OPTIONS = [
        ['type' => 'school', 'suffix' => 'Elementary School'],
        ['type' => 'school', 'suffix' => 'National High School'],
        ['type' => 'barangay_hall', 'suffix' => 'Barangay Hall'],
        ['type' => 'covered_court', 'suffix' => 'Covered Court'],
        ['type' => 'gymnasium', 'suffix' => 'Multipurpose Gymnasium'],
        ['type' => 'church', 'suffix' => 'Parish Chapel'],
    ];

    // barangay_range/families_per_barangay_range escalate with severity,
    // same principle as DemoDisasterDataSeeder's historical events -- stays
    // within 6-10 barangays total across all 4 variants, per spec.
    private const VARIANTS = [
        1 => ['name' => 'Tropical Storm Amang', 'rainfall_mm' => 140, 'wind_speed_kph' => 70, 'label' => 'mild', 'barangay_range' => [6, 6], 'families_per_barangay_range' => [2, 3]],
        2 => ['name' => 'Typhoon Bagwis', 'rainfall_mm' => 220, 'wind_speed_kph' => 110, 'label' => 'moderate', 'barangay_range' => [7, 7], 'families_per_barangay_range' => [3, 4]],
        3 => ['name' => 'Typhoon Diwata', 'rainfall_mm' => 310, 'wind_speed_kph' => 160, 'label' => 'strong', 'barangay_range' => [8, 9], 'families_per_barangay_range' => [4, 5]],
        4 => ['name' => 'Typhoon Hagibis-PH', 'rainfall_mm' => 380, 'wind_speed_kph' => 190, 'label' => 'severe', 'barangay_range' => [9, 10], 'families_per_barangay_range' => [5, 7]],
    ];

    public function run(): void
    {
        $rawVariant = env('DEMO_VARIANT');

        if ($rawVariant === null || $rawVariant === '') {
            $this->command->error(
                'DEMO_VARIANT is required. Usage: DEMO_VARIANT=1 php artisan db:seed --class=DemoActiveEventSeeder (variant 1-4).'
            );

            return;
        }

        $variant = (int) $rawVariant;

        if (! isset(self::VARIANTS[$variant])) {
            $this->command->error("Invalid DEMO_VARIANT '{$rawVariant}' -- must be 1, 2, 3, or 4.");

            return;
        }

        $barangays = Barangay::all();
        if ($barangays->count() < 8) {
            $this->command->error('Need at least 8 seeded barangays -- run BarangaySeeder first.');

            return;
        }

        $this->wipeExistingVariant();

        $data = self::VARIANTS[$variant];

        $event = EvacuationEvent::create([
            'name' => $data['name'],
            'event_type' => 'typhoon',
            'typhoon_category' => str_starts_with($data['name'], 'Tropical Storm') ? 'Tropical Storm' : 'Typhoon',
            'max_wind_speed_kph' => $data['wind_speed_kph'],
            'rainfall_mm' => $data['rainfall_mm'],
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'status' => 'active',
            'description' => "Demo/sample ACTIVE disaster scenario (variant {$variant}, {$data['label']}) for live demo/defense purposes -- entirely fabricated test data, not a real event.",
        ]);

        [$barangayMin, $barangayMax] = $data['barangay_range'];
        [$familyMin, $familyMax] = $data['families_per_barangay_range'];

        $totals = ['centers' => 0, 'families' => 0, 'evacuees' => 0];

        $barangayCount = min($barangays->count(), rand($barangayMin, $barangayMax));
        foreach ($barangays->random($barangayCount) as $barangay) {
            $center = $this->resolveCenterFor($barangay, $totals);

            $familyCount = rand($familyMin, $familyMax);
            for ($i = 0; $i < $familyCount; $i++) {
                $totals['evacuees'] += $this->createFamily($event, $barangay, $center);
                $totals['families']++;
            }
        }

        $this->command->info('');
        $this->command->info("Variant {$variant} seeded: {$data['name']} ({$data['label']})");
        $this->command->info("  Rainfall: {$data['rainfall_mm']} mm");
        $this->command->info("  Max wind speed: {$data['wind_speed_kph']} kph");
        $this->command->info('  (copy these two numbers into Predictive Analytics\' "Generate forecast" input for a coherent before/after comparison)');
        $this->command->info("  Families created: {$totals['families']}");
        $this->command->info("  Evacuees created: {$totals['evacuees']}");
        $this->command->info("  New sample centers created: {$totals['centers']}");
    }

    /**
     * Removes whichever of the 4 known variant events currently exists
     * (matched by exact name -- these 4 names are unique to this seeder
     * and never overlap with DemoDisasterDataSeeder's historical events or
     * any real event), so switching variants never leaves stale data
     * sitting alongside the newly seeded one. Families/evacuees/evacuation
     * records/prediction_datasets cascade automatically on delete (see the
     * evacuation_events migration); alerts and analytics_predictions use
     * nullOnDelete instead of cascading, so those are cleaned up
     * explicitly first. Sample evacuation centers are deliberately left
     * alone -- they're reused across variants (and by DemoDisasterDataSeeder
     * too), same as that seeder's own convention.
     */
    private function wipeExistingVariant(): void
    {
        $eventIds = EvacuationEvent::whereIn('name', array_column(self::VARIANTS, 'name'))->pluck('id');

        if ($eventIds->isEmpty()) {
            return;
        }

        Alert::whereIn('evacuation_event_id', $eventIds)->delete();
        AnalyticsPrediction::whereIn('evacuation_event_id', $eventIds)->delete();
        EvacuationEvent::whereIn('id', $eventIds)->delete();

        $this->command->info('Removed previously-seeded active demo event and its dependent data.');
    }

    /**
     * Reuses a barangay's real (non-seeded) evacuation center if one
     * already exists; otherwise reuses (or creates) its is_seeded=true
     * placeholder -- matched via the flag, not name, since a seeded
     * center's name is now randomly varied (see CENTER_TYPE_OPTIONS)
     * rather than a predictable "[SAMPLE] ..." string. This also matches
     * against centers DemoDisasterDataSeeder already created for the same
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
     * ACTIVE event -- unlike DemoDisasterDataSeeder's historical/closed
     * families (created already "returned home"), these evacuees are
     * created currently checked in, so they actually show up in today's
     * live dashboard stats and current occupancy figures.
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
                'status' => 'active',
            ]);

            if ($isHead) {
                $headOfFamilyId = $evacuee->id;
            }

            EvacuationRecord::create([
                'evacuee_id' => $evacuee->id,
                'evacuation_center_id' => $center->id,
                'evacuation_event_id' => $event->id,
                'displacement_type' => 'inside_center',
                'date_in' => now(),
                'date_out' => null,
                'status' => 'currently_evacuated',
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
