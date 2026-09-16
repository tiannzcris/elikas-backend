<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Normalizes the EC Information Board's age/sex disaggregation table
    // into rows (one per age bracket) instead of 14 flat male/female
    // columns on the parent row -- same reasoning as the existing
    // evacuation_center_facilities table (see its migration): a fixed
    // checklist of categories, each carrying the same couple of counters,
    // reads and updates more cleanly as rows than as a wide flat table.
    public function up(): void
    {
        Schema::create('evacuation_center_quick_count_age_groups', function (Blueprint $table) {
            $table->id();
            // Explicit short FK name: the auto-generated one (derived from
            // both table names) exceeds MySQL's 64-character identifier limit.
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

    public function down(): void
    {
        Schema::dropIfExists('evacuation_center_quick_count_age_groups');
    }
};
