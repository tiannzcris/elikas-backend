<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Lets an EC Board age/sex breakdown ("3 male adults") generate real
    // placeholder Evacuee records without fabricating a fake date_of_birth
    // to make Evacuee::age_bracket compute the right bucket. This column is
    // write-once bookkeeping, not live truth: set when a placeholder is
    // generated, and deliberately left untouched even after the record is
    // later completed with a real date_of_birth (see
    // Evacuee::getAgeBracketAttribute(), which always prefers a real
    // date_of_birth over this column when one exists). That permanence is
    // what lets EcBoardPlaceholderSyncService reconcile "how many
    // placeholders did this bracket/sex originally represent, completed or
    // not" on a repeat save, instead of losing that history the moment
    // someone fills in a person's real details.
    public function up(): void
    {
        Schema::table('evacuees', function (Blueprint $table) {
            $table->enum('age_bracket_override', [
                'infant', 'toddler', 'preschooler', 'school_age', 'teenage', 'adult', 'senior_citizen',
            ])->nullable()->after('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::table('evacuees', function (Blueprint $table) {
            $table->dropColumn('age_bracket_override');
        });
    }
};
