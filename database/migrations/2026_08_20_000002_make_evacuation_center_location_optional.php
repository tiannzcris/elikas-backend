<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The spatial 'location' POINT column (and its index) existed ONLY to
    // power the old ST_Distance_Sphere() nearest-center query -- nothing
    // else in this codebase reads it (the GIS map, public center list,
    // etc. all read the plain latitude/longitude columns directly). It was
    // NOT NULL specifically because MySQL/MariaDB don't allow spatial
    // indexes on nullable columns (see the removed comment in
    // EvacuationCenter::booted()) -- a hard constraint that directly
    // conflicts with making location genuinely optional. Dropping it and
    // rewriting the nearest-center query as a plain Haversine calculation
    // over latitude/longitude (see EvacuationCenterController::nearest())
    // resolves that conflict outright instead of working around it.
    public function up(): void
    {
        DB::statement('ALTER TABLE evacuation_centers DROP INDEX location_spatial_idx');
        DB::statement('ALTER TABLE evacuation_centers DROP COLUMN location');

        DB::statement('ALTER TABLE evacuation_centers MODIFY latitude DECIMAL(10,7) NULL');
        DB::statement('ALTER TABLE evacuation_centers MODIFY longitude DECIMAL(10,7) NULL');
    }

    public function down(): void
    {
        // Only safe to reverse if no row currently has a null coordinate --
        // this will fail loudly (not silently corrupt data) if any do,
        // which is the correct behavior for a rollback.
        DB::statement('ALTER TABLE evacuation_centers MODIFY latitude DECIMAL(10,7) NOT NULL');
        DB::statement('ALTER TABLE evacuation_centers MODIFY longitude DECIMAL(10,7) NOT NULL');

        DB::statement('ALTER TABLE evacuation_centers ADD location POINT NOT NULL');
        DB::statement('ALTER TABLE evacuation_centers ADD SPATIAL INDEX location_spatial_idx (location)');
    }
};
