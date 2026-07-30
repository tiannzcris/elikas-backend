<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hazard_prone_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barangay_id')->nullable()->constrained()->nullOnDelete();
            $table->string('area_name', 150);
            $table->enum('hazard_type', ['flood', 'landslide', 'lahar', 'storm_surge', 'volcanic_danger_zone']);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Spatial POLYGON column, added via raw statement after the table
        // above actually exists (Laravel's schema builder has no native
        // spatial column methods as of Laravel 11+). No inline SRID: that
        // column-level modifier is MySQL-8-only and MariaDB's parser
        // rejects it -- portable across both engines by omitting it here;
        // the app layer sets SRID 4326 at insert time via ST_GeomFromText().
        DB::statement('ALTER TABLE hazard_prone_areas ADD geometry POLYGON NOT NULL');
        DB::statement('ALTER TABLE hazard_prone_areas ADD SPATIAL INDEX geometry_spatial_idx (geometry)');
    }

    public function down(): void
    {
        Schema::dropIfExists('hazard_prone_areas');
    }
};
