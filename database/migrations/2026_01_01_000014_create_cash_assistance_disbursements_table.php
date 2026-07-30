<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Corresponds to the AICS, AKAP, ECT, CFW, and SLP columns of the DROMIC
    // Region V report. One row per program per barangay per event, so a
    // barangay receiving both AICS and CFW during one typhoon gets two rows.
    public function up(): void
    {
        Schema::create('cash_assistance_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
            $table->enum('program', ['AICS', 'AKAP', 'ECT', 'CFW', 'SLP']);
            $table->unsignedInteger('number_of_beneficiaries');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('total_cost', 12, 2)->storedAs('number_of_beneficiaries * unit_cost');
            $table->dateTime('disbursed_at');
            $table->timestamps();

            $table->unique(['evacuation_event_id', 'barangay_id', 'program'], 'cash_assist_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_assistance_disbursements');
    }
};
