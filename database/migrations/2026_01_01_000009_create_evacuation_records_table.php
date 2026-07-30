<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // One row per evacuee per stay. displacement_type distinguishes evacuees
    // physically inside a center from those who evacuated to relatives/other
    // locations (DROMIC reports both categories separately).
    public function up(): void
    {
        Schema::create('evacuation_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evacuation_center_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->enum('displacement_type', ['inside_center', 'outside_center']);
            $table->dateTime('date_in');
            $table->dateTime('date_out')->nullable();
            $table->enum('status', ['currently_evacuated', 'returned_home', 'transferred'])->default('currently_evacuated');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['evacuation_center_id', 'status']);
            $table->index(['evacuation_event_id', 'displacement_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuation_records');
    }
};
