<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Superseded by live computation: the age/sex breakdown is no longer a
    // manually-typed number reconciled against generated placeholder
    // evacuees (EcBoardPlaceholderSyncService, now removed) -- it's
    // computed directly from real Evacuee/EvacuationRecord rows every time
    // the board is viewed (see EvacuationCenterQuickCount::liveAgeSexBreakdown()).
    // Nothing to migrate forward: this table only ever held a derived,
    // reconciled snapshot, not source-of-truth data.
    public function up(): void
    {
        Schema::dropIfExists('evacuation_center_quick_count_age_groups');
    }

    public function down(): void
    {
        Schema::create('evacuation_center_quick_count_age_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_center_quick_count_id')
                ->constrained('evacuation_center_quick_counts', indexName: 'ec_qc_age_group_fk')
                ->cascadeOnDelete();
            $table->enum('age_bracket', [
                'infant', 'toddler', 'preschooler', 'school_age', 'teenage', 'adult', 'senior_citizen',
            ]);
            $table->unsignedInteger('male_count')->default(0);
            $table->unsignedInteger('female_count')->default(0);
            $table->timestamps();

            $table->unique(['evacuation_center_quick_count_id', 'age_bracket'], 'ec_qc_age_group_unique');
        });
    }
};
