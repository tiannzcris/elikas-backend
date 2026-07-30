<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            // Step 3 onward: EvacuationCenterSeeder, BarangaySeeder (all 46
            // Ligao City barangays), etc. will be added here.
        ]);
    }
}
