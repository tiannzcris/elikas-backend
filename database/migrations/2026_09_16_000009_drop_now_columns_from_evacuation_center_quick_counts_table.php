<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // families_now/persons_now stop being stored, manually-typed figures --
    // "Now" is live-computed from actual EvacuationRecord/Evacuee data for
    // this center+event (see EvacuationCenterQuickCount::livePersonsNow()/
    // liveFamiliesNow()), the same live-aggregate approach the age/sex
    // breakdown now uses too. Only families_cumulative/persons_cumulative
    // remain as real stored columns -- "Now" can always be recomputed from
    // current records, but cumulative specifically needs to survive a
    // record being removed later, which a live query can't do.
    public function up(): void
    {
        Schema::table('evacuation_center_quick_counts', function (Blueprint $table) {
            $table->dropColumn(['families_now', 'persons_now']);
        });
    }

    public function down(): void
    {
        Schema::table('evacuation_center_quick_counts', function (Blueprint $table) {
            $table->unsignedInteger('families_now')->default(0)->after('families_cumulative');
            $table->unsignedInteger('persons_now')->default(0)->after('persons_cumulative');
        });
    }
};
