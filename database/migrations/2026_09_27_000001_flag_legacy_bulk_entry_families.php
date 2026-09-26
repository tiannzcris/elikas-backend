<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Flags the leftover "EC Board bulk entry" households from the removed
    // typed-count mechanism (see 2026_09_16_000007/000011): one synthetic
    // family per board, holding anonymous placeholders generated from a
    // typed headcount -- NOT a real household. Found by what actually
    // identifies them rather than by id (ids differ between databases): no
    // head on record, AND either the old mechanism's "EC Board Entry" name
    // or 3+ members created in the same second, which one-at-a-time
    // registration never does. Their people are left exactly where they
    // are (splitting them into real households needs someone who knows
    // who's who); they're only flagged, relabelled, and -- see
    // EvacuationCenterController -- closed to new members.
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->boolean('is_legacy_bulk_entry')->default(false)->after('head_sex');
        });

        $bulkBySecond = DB::table('evacuees')
            ->select('family_id')
            ->whereNotNull('family_id')
            ->groupByRaw("family_id, DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s')")
            ->havingRaw('COUNT(*) >= 3')
            ->pluck('family_id')
            ->unique();

        $legacy = DB::table('families')
            ->whereNull('head_of_family_evacuee_id')
            ->where(fn ($q) => $q->where('name', 'like', 'EC Board Entry%')->orWhereIn('id', $bulkBySecond))
            ->get(['id']);

        foreach ($legacy as $family) {
            // The center most of its people are recorded at, for the label.
            $centerName = DB::table('evacuation_records as r')
                ->join('evacuees as e', 'e.id', '=', 'r.evacuee_id')
                ->join('evacuation_centers as c', 'c.id', '=', 'r.evacuation_center_id')
                ->where('e.family_id', $family->id)
                ->groupBy('c.id', 'c.name')
                ->orderByRaw('COUNT(*) DESC')
                ->value('c.name');

            DB::table('families')->where('id', $family->id)->update([
                'is_legacy_bulk_entry' => true,
                'name' => '[Legacy] Unassigned headcount'.($centerName ? " -- {$centerName}" : '').' (do not add people here)',
            ]);
        }
    }

    public function down(): void
    {
        // Relabelled names aren't restored -- the old ones were either empty
        // or the mechanism's own "EC Board Entry -- ..." label.
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn('is_legacy_bulk_entry');
        });
    }
};
