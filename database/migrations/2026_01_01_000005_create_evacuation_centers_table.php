<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evacuation_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barangay_id')->constrained('barangays')->restrictOnDelete();
            $table->string('name', 150);
            $table->enum('type', ['school', 'covered_court', 'church', 'barangay_hall', 'gymnasium', 'other']);
            $table->text('address');
            // Spatial 'location' column is added below via raw SQL, after this
            // table exists -- Laravel 11+ removed the point()/spatialIndex()
            // Blueprint methods used in an earlier version of this migration,
            // so we add it the same way as hazard_prone_areas.geometry.
            $table->decimal('latitude', 10, 7);   // kept denormalized too, for simple Leaflet/flutter_map consumption
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('capacity_families')->nullable();
            $table->unsignedInteger('capacity_persons')->nullable();
            $table->string('camp_manager_name', 150)->nullable();
            $table->string('camp_manager_contact', 20)->nullable();
            $table->enum('status', ['active', 'full', 'closed', 'on_standby'])->default('on_standby');
            $table->timestamps();

            $table->index(['barangay_id', 'status']);
        });

        // Spatial POINT column -> enables ST_Distance_Sphere queries for
        // "nearest evacuation center" without pulling all rows into PHP.
        // No inline SRID here: that column-level modifier is a MySQL-8-only
        // addition that MariaDB's parser rejects. Portable across both
        // engines by omitting it; the app layer sets SRID 4326 (WGS84,
        // ordinary lat/lng) at insert time via ST_GeomFromText(wkt, 4326).
        DB::statement('ALTER TABLE evacuation_centers ADD location POINT NOT NULL');
        DB::statement('ALTER TABLE evacuation_centers ADD SPATIAL INDEX location_spatial_idx (location)');
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuation_centers');
    }
};
