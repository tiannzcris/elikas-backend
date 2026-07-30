<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damaged_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('barangay_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('totally_damaged_count')->default(0);
            $table->unsignedInteger('partially_damaged_count')->default(0);
            $table->dateTime('recorded_at');
            $table->timestamps();

            // One damage tally per barangay per event; officials update the same
            // row as assessments are refined instead of inserting duplicates.
            $table->unique(['evacuation_event_id', 'barangay_id'], 'damaged_houses_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damaged_houses');
    }
};
