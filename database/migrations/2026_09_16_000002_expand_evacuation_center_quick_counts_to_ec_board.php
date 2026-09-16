<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Widens the original 3-field (family/male/female) quick count into the
    // real EC Information Board's "Displaced Families/Persons" section:
    // Cumulative (running total for the whole event) and Now (currently
    // inside the center) are genuinely different figures a camp manager
    // tracks separately -- not derivable from one another -- so both get
    // their own column, for both families and persons. beneficiaries_4ps is
    // the board's single "no. of 4Ps beneficiary families" figure (distinct
    // from the four_ps_beneficiary row in the sectoral breakdown table added
    // alongside this, which counts affected PERSONS by sex instead).
    public function up(): void
    {
        Schema::table('evacuation_center_quick_counts', function (Blueprint $table) {
            $table->dropColumn(['family_count', 'male_count', 'female_count']);

            $table->unsignedInteger('families_cumulative')->default(0)->after('evacuation_event_id');
            $table->unsignedInteger('families_now')->default(0)->after('families_cumulative');
            $table->unsignedInteger('persons_cumulative')->default(0)->after('families_now');
            $table->unsignedInteger('persons_now')->default(0)->after('persons_cumulative');
            $table->unsignedInteger('beneficiaries_4ps')->default(0)->after('persons_now');
        });
    }

    public function down(): void
    {
        Schema::table('evacuation_center_quick_counts', function (Blueprint $table) {
            $table->dropColumn(['families_cumulative', 'families_now', 'persons_cumulative', 'persons_now', 'beneficiaries_4ps']);

            $table->unsignedInteger('family_count')->default(0)->after('evacuation_event_id');
            $table->unsignedInteger('male_count')->default(0)->after('family_count');
            $table->unsignedInteger('female_count')->default(0)->after('male_count');
        });
    }
};
