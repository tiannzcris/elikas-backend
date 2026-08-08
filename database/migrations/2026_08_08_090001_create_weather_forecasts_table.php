<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Stores the OUTPUT of a SARIMA/SARIMAX forecast run against
    // weather_readings -- mirrors analytics_predictions' role for the
    // linear-regression feature (history list + audit trail), kept as a
    // fully separate table since this is a parallel, unrelated model.
    public function up(): void
    {
        Schema::create('weather_forecasts', function (Blueprint $table) {
            $table->id();
            $table->string('metric', 30)->comment('rainfall_mm or wind_speed_kph');
            $table->unsignedInteger('horizon')->comment('Number of future periods forecasted');
            $table->json('forecast_points')->comment('[{date, predicted, lower_ci, upper_ci}, ...]');
            $table->json('diagnostics')->nullable()->comment('{aic, n_observations, order, seasonal_order, model}');
            // Propagated from whether ANY weather_readings row used to train
            // this specific run was is_sample=true -- mirrors the
            // used_default_ratios honesty marker on analytics_predictions.
            // Never let a forecast with this true be presented as real.
            $table->boolean('is_sample_based')->default(false);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('generated_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_forecasts');
    }
};
