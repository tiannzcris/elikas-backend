<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // "Others" as a check-out reason (Mark family as departed, and the
    // single Check out endpoint it reuses): a person who left for some
    // reason other than going home or being transferred. Added to both
    // status columns EvacuationRecord::checkOut() writes. Additive only --
    // every live count keys off 'currently_evacuated' / date_out, so a new
    // departed value changes no figure.
    public function up(): void
    {
        Schema::table('evacuation_records', function (Blueprint $table) {
            $table->enum('status', ['currently_evacuated', 'returned_home', 'transferred', 'other'])
                ->default('currently_evacuated')->change();
        });

        Schema::table('evacuees', function (Blueprint $table) {
            $table->enum('status', ['active', 'returned_home', 'transferred', 'deceased', 'other'])
                ->default('active')->change();
        });
    }

    // Only safe while no row uses 'other' yet; those rows would fail the
    // narrower enum.
    public function down(): void
    {
        Schema::table('evacuation_records', function (Blueprint $table) {
            $table->enum('status', ['currently_evacuated', 'returned_home', 'transferred'])
                ->default('currently_evacuated')->change();
        });

        Schema::table('evacuees', function (Blueprint $table) {
            $table->enum('status', ['active', 'returned_home', 'transferred', 'deceased'])
                ->default('active')->change();
        });
    }
};
