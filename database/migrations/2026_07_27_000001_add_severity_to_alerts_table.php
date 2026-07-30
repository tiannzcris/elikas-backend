<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Adds urgency/severity as a NEW, separate field from alert_type.
    // alert_type already categorizes alerts by disaster (typhoon, flood,
    // etc.) -- that's still useful for DROMIC-style reporting. This is a
    // different axis: how urgently a resident needs to act on THIS
    // specific alert, regardless of which disaster it's about. A typhoon
    // alert could reasonably be a mandatory evacuation order today and
    // just an advisory update tomorrow.
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->enum('severity', ['mandatory', 'advisory', 'info', 'all_clear'])
                ->default('advisory')
                ->after('alert_type');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn('severity');
        });
    }
};
