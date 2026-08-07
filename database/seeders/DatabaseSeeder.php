<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            BarangaySeeder::class,
            // Step 3 onward: EvacuationCenterSeeder, etc. will be added here.
        ]);
    }
}
