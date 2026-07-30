<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A "disaster event" is the anchor for everything else: an evacuation_event
    // groups all evacuee records, relief distributions, and DROMIC report rows
    // that occurred because of one specific typhoon/flood/eruption episode.
    public function up(): void
    {
        Schema::create('evacuation_events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150); // e.g. "Typhoon Rolly 2026", "Mayon Unrest Alert Level 3"
            $table->enum('event_type', ['typhoon', 'flood', 'volcanic_eruption', 'earthquake', 'other']);
            // Environmental inputs used later as predictive-analytics features
            $table->string('typhoon_category', 30)->nullable()->comment('e.g. Signal No. 3, Super Typhoon');
            $table->decimal('max_wind_speed_kph', 6, 2)->nullable();
            $table->decimal('rainfall_mm', 8, 2)->nullable();
            $table->string('alert_level', 30)->nullable()->comment('For volcanic events, e.g. Alert Level 3');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['monitoring', 'active', 'closed'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evacuation_events');
    }
};
