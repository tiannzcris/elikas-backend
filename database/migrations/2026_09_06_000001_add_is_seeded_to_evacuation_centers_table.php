<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Introduces a real, queryable is_seeded flag to distinguish seeder-
     * created evacuation centers from genuine CSWDO-verified ones --
     * replacing the "[SAMPLE] " name prefix (DemoActiveEventSeeder /
     * DemoDisasterDataSeeder), which was purely cosmetic and had no actual
     * DB-level flag behind it.
     *
     * Also retroactively fixes any center already created under the old
     * prefix convention: sets is_seeded = true and strips "[SAMPLE] " from
     * its name, so existing seeded rows end up in the same state a
     * freshly-seeded row will be in going forward. Only rows matching that
     * exact prefix are touched -- a real, non-prefixed record (e.g.
     * "Baligang Elementary School") is untouched and keeps the column's
     * default is_seeded = false.
     */
    public function up(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->boolean('is_seeded')->nullable()->default(false)->after('photo_path');
        });

        DB::table('evacuation_centers')
            ->where('name', 'like', '[SAMPLE]%')
            ->get(['id', 'name'])
            ->each(function ($row) {
                DB::table('evacuation_centers')->where('id', $row->id)->update([
                    'name' => preg_replace('/^\[SAMPLE\]\s+/', '', $row->name),
                    'is_seeded' => true,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('evacuation_centers', function (Blueprint $table) {
            $table->dropColumn('is_seeded');
        });
    }
};
