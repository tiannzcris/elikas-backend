<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The "one synthetic bulk-entry family per quick count" model is gone:
    // EC Board's "Add Evacuee" now creates/attaches to REAL, individually
    // distinct households (see EvacuationCenterController::addEvacuee()),
    // not one big shared placeholder family reconciled against a typed
    // count. This FK only ever existed to let EcBoardPlaceholderSyncService
    // find that one shared family again on a repeat save -- both the
    // service and the concept it supported are removed.
    //
    // Non-destructive: this drops the FK/column only. The families and
    // evacuees it used to point to are NOT deleted -- they remain as
    // ordinary (if now unlabeled by this mechanism) families, exactly as
    // any other registered household. Their families.name value (e.g. "EC
    // Board Entry -- Some Center") is left as-is too, a harmless leftover
    // label from the old mechanism rather than something worth a data
    // migration to rename.
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evacuation_center_quick_count_id');
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('evacuation_center_quick_count_id')->nullable()->unique()
                ->after('evacuation_event_id')
                ->constrained('evacuation_center_quick_counts')->nullOnDelete();
        });
    }
};
