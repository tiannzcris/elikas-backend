<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'administrator')->first();

        if (! $adminRole) {
            $this->command->error('Roles are not seeded yet -- run migrations first (roles are seeded inside the roles migration).');
            return;
        }

        // Read through config(), not env() directly -- raw env() calls
        // outside config files can silently return stale/empty values once
        // `php artisan config:cache` has run in production (exactly what
        // happened during initial deployment: the seeder ran once with a
        // stale cached config before `config:clear` was run, using an empty
        // password). config('elikas.admin_seed...') is reliably correct
        // whether or not config is cached.
        $email = config('elikas.admin_seed.email');
        $password = config('elikas.admin_seed.password');

        if (! $email || ! $password) {
            $this->command->error('ADMIN_SEED_EMAIL and ADMIN_SEED_PASSWORD must both be set in .env before running this seeder.');
            return;
        }

        // firstOrCreate so re-running `php artisan db:seed` never duplicates
        // or resets this account once it exists.
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'role_id' => $adminRole->id,
                'barangay_id' => null,
                'name' => 'System Administrator',
                'password' => bcrypt($password),
                'contact_number' => null,
                'status' => 'active',
            ]
        );

        $this->command->info("Administrator ready -> email: {$user->email}");
        $this->command->warn('Change this password immediately after your first login.');
    }
}
