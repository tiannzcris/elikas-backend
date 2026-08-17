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

        // DemoDisasterDataSeeder (demo/sample disaster data for testing
        // predictive analytics) is deliberately NOT chained here -- it's
        // testing data, not something a real deployment should get
        // automatically. Run it explicitly when needed:
        //   php artisan db:seed --class=DemoDisasterDataSeeder
    }
}
