<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Feature store for the PHP-ML linear regression model. Each row is one
    // (parameter, value) pair for one past disaster event -- e.g.
    // ('rainfall_mm', 180.5) or ('actual_families_displaced', 312). Kept as
    // parameter/value rather than fixed columns so new predictive features
    // can be added later without another migration.
    public function up(): void
    {
        Schema::create('prediction_datasets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evacuation_event_id')->constrained()->cascadeOnDelete();
            $table->string('parameter', 100)->comment('e.g. rainfall_mm, wind_speed_kph, actual_families_displaced');
            $table->decimal('value', 12, 2);
            $table->string('unit', 30)->nullable();
            $table->dateTime('recorded_at');
            $table->timestamps();

            $table->index(['evacuation_event_id', 'parameter']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_datasets');
    }
};
