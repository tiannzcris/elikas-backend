<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Regular (daily/weekly) historical weather readings imported from PAGASA
    // CSV exports -- a genuine time series, unlike evacuation_events' single
    // rainfall/wind reading per disaster event. Feeds the SARIMA/SARIMAX
    // forecasting pipeline in weather-forecasts, kept entirely separate from
    // the existing linear-regression predictive analytics feature.
    public function up(): void
    {
        Schema::create('weather_readings', function (Blueprint $table) {
            $table->id();
            $table->date('reading_date');
            // NOT NULL with a default so the unique index below behaves
            // predictably -- MySQL treats each NULL as distinct in a unique
            // key, which would silently defeat de-duplication if this were
            // nullable instead.
            $table->string('station', 100)->default('default');
            // Independently nullable: a CSV row reporting only rainfall (a
            // common basic PAGASA export shape) is legitimate, and blank
            // must map to NULL, not 0 -- 0 is a real "no rain recorded"
            // observation, not a missing one.
            $table->decimal('rainfall_mm', 8, 2)->nullable();
            $table->decimal('wind_speed_kph', 6, 2)->nullable();
            $table->boolean('is_sample')->default(false)
                ->comment('TRUE = fabricated/synthetic test data for pipeline testing, NOT real PAGASA data.');
            $table->string('source_file', 255)->nullable()->comment('Original CSV filename, for import traceability');
            $table->timestamps();

            $table->unique(['reading_date', 'station']);
            $table->index('reading_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_readings');
    }
};
