<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Same rows-not-flat-columns normalization as the age groups table
    // above, for the EC Board's sectoral group breakdown. Deliberately a
    // separate table rather than reusing the age groups one with a
    // "group type" discriminator column -- the two lists (age brackets vs
    // sectoral categories) are unrelated enough, and never queried
    // together, that sharing a table would only mean every row-level
    // unique constraint and query has to filter on group type first.
    public function up(): void
    {
        Schema::create('evacuation_center_quick_count_sectoral_groups', function (Blueprint $table) {
            $table->id();
            // Explicit short FK name: the auto-generated one (derived from
            // both table names) exceeds MySQL's 64-character identifier limit.
            $table->foreignId('evacuation_center_quick_count_id')
                ->constrained('evacuation_center_quick_counts', indexName: 'ec_qc_sectoral_group_fk')
                ->cascadeOnDelete();
            $table->enum('sectoral_group', [
                'pwd', 'child_headed_family', 'single_headed_family', 'solo_parent',
                'pregnant_women', 'lactating_mothers', 'four_ps_beneficiary', 'indigenous_peoples',
            ]);
            $table->unsignedInteger('male_count')->default(0);
            $table->unsignedInteger('female_count')->default(0);
            $table->timestamps();

            $table->unique(['evacuation_center_quick_count_id', 'sectoral_group'], 'ec_qc_sectoral_group_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuation_center_quick_count_sectoral_groups');
    }
};
