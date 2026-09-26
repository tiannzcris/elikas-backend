<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Household-level answers behind the EC Board's Child-Headed and
    // Single-Headed Family rows (see Family::isSingleHeaded()/
    // isChildHeaded()/headSex()). All nullable: null means "not yet known",
    // never "no" -- existing families simply start there.
    public function up(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->boolean('is_single_headed')->nullable()->after('is_4ps_beneficiary');
            // The answer to "is the head a minor?", used only while the
            // linked head has no real date_of_birth -- a birthdate always wins.
            $table->boolean('head_is_minor')->nullable()->after('is_single_headed');
            // The head's sex when no head evacuee is linked yet; a linked
            // head's own sex always wins.
            $table->enum('head_sex', ['male', 'female'])->nullable()->after('head_is_minor');
        });
    }

    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['is_single_headed', 'head_is_minor', 'head_sex']);
        });
    }
};
