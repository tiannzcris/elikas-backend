<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique(); // barangay_official, cswd_personnel, resident, administrator
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed the four roles defined in Chapter 1 scope
        DB::table('roles')->insert([
            ['name' => 'administrator', 'display_name' => 'System Administrator', 'description' => 'Full system access, user management, backups.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cswd_personnel', 'display_name' => 'CSWD Personnel', 'description' => 'Monitoring, reporting, alerts, predictive analytics, GIS management.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'barangay_official', 'display_name' => 'Barangay Official', 'description' => 'Evacuee registration and barangay-level reports.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'resident', 'display_name' => 'Resident', 'description' => 'Mobile app read access; no login required per scope.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
