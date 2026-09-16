<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Marks a Family row as the single "EC Board bulk entry" household for
    // one evacuation_center_quick_counts row -- see EcBoardPlaceholderSyncService.
    // Nullable + unique: every normal, individually-registered family has
    // this null (unaffected); at most one bulk family exists per quick
    // count, found via this FK rather than any naming convention, so it
    // stays reliably findable across repeat EC Board saves regardless of
    // who's viewing/editing it. nullOnDelete (not cascade): if a quick
    // count row is ever deleted, the real evacuee records it generated are
    // NOT deleted with it -- they're real registered people by that point,
    // the same as any other family.
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->foreignId('evacuation_center_quick_count_id')->nullable()->unique()
                ->after('evacuation_event_id')
                ->constrained('evacuation_center_quick_counts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropConstrainedForeignId('evacuation_center_quick_count_id');
        });
    }
};
