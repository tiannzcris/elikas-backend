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

        // Read from .env rather than hardcoded here -- keeps the actual
        // credentials out of source control (Laravel's default
        // .gitignore already excludes .env, so nothing extra needed for
        // that part). Falls back to a documented default only if .env
        // doesn't set these, so a fresh clone still works out of the box.
        $email = env('ADMIN_SEED_EMAIL', 'admin@elikas.ligaocity.gov.ph');
        $password = env('ADMIN_SEED_PASSWORD', 'ChangeMe123!');

        // firstOrCreate so re-running `php artisan db:seed` never duplicates
        // or resets this account once it exists.
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'role_id' => $adminRole->id,
                'barangay_id' => null,
                'name' => 'Ligao CSWDO',
                'password' => bcrypt($password),
                'contact_number' => null,
                'status' => 'active',
            ]
        );

        $this->command->info("Administrator ready -> email: {$user->email}");
        $this->command->warn('Change this password immediately after your first login.');
    }
}
