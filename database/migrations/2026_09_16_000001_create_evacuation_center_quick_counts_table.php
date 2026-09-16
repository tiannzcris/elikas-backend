<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Fast aggregate headcount reported by whichever staff member is on
    // hand during an active evacuation -- deliberately separate from the
    // detailed families/evacuees registration flow, which is too slow for
    // the "how many people do we have RIGHT NOW" question CSWDO needs
    // answered first. One row per (center, event): it gets overwritten as
    // more families arrive, not appended to, and is scoped to the event so
    // a past typhoon's headcount never bleeds into a new one at the same
    // center.
    public function up(): void
    {
        Schema::create('evacuation_center_quick_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('family_count')->default(0);
            $table->unsignedInteger('male_count')->default(0);
            $table->unsignedInteger('female_count')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['evacuation_center_id', 'evacuation_event_id'], 'ec_quick_count_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuation_center_quick_counts');
    }
};
