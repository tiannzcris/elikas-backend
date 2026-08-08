<?php

namespace App\Services;

use App\Exceptions\ForecastServiceUnavailableException;
use App\Models\WeatherForecast;
use App\Models\WeatherReading;
use Carbon\Carbon;

/**
 * Orchestrates a SARIMA/SARIMAX forecast: pulls the historical time series
 * from weather_readings, calls the Python microservice via
 * SarimaForecastService, and persists the result. Entirely separate from
 * PredictiveAnalyticsService's linear regression -- different data source
 * (a real time series, not one row per disaster event), different model,
 * different failure modes.
 */
class WeatherForecastService
{
    private const MIN_READINGS_TO_FORECAST = 14;

    public function __construct(private readonly SarimaForecastService $sarima)
    {
    }

    /**
     * @throws \RuntimeException if there isn't enough local weather_readings
     *   data yet -- an expected, explainable state (import more data), not
     *   a server error. Mirrors PredictiveAnalyticsService::predict()'s
     *   exact pattern.
     * @throws ForecastServiceUnavailableException if the local data is fine
     *   but the Python service itself is unreachable/unconfigured/erroring.
     */
    public function forecast(string $metric, int $horizon = 14, ?string $station = null, ?int $requestedBy = null): WeatherForecast
    {
        $query = WeatherReading::query()
            ->whereNotNull($metric)
            ->orderBy('reading_date');

        if ($station) {
            $query->where('station', $station);
        }

        $readings = $query->get();

        if ($readings->count() < self::MIN_READINGS_TO_FORECAST) {
            throw new \RuntimeException(
                'Not enough weather history yet to generate a SARIMA forecast. At least '.
                self::MIN_READINGS_TO_FORECAST.' readings with a recorded '.$metric.
                ' value are needed (currently have '.$readings->count().'). Import more PAGASA '.
                'data, or run `php artisan weather:import ... --sample` to build up test data '.
                'while waiting on real records.'
            );
        }

        $daysPerPeriod = $this->computeDaysPerPeriod($readings->pluck('reading_date')->all());
        $seasonalPeriods = match ($daysPerPeriod) {
            1 => 7,   // weekly seasonality in daily data
            7 => 4,   // ~monthly seasonality in weekly data
            default => null, // let the SARIMA service apply its own default
        };

        $series = $readings->map(fn (WeatherReading $r) => [
            'date' => $r->reading_date->format('Y-m-d'),
            'value' => $r->{$metric} !== null ? (float) $r->{$metric} : null,
        ])->values()->all();

        $isSampleBased = $readings->contains('is_sample', true);

        $result = $this->sarima->forecast($metric, $series, $horizon, $daysPerPeriod, $seasonalPeriods);

        if (($result['success'] ?? false) !== true) {
            $reason = $result['reason'] ?? 'unknown';
            $message = match ($reason) {
                'not_configured' => 'The SARIMA forecasting service isn\'t configured yet on this deployment (SARIMA_SERVICE_URL is not set).',
                'service_error' => 'The SARIMA forecasting service returned an error: '.($result['details']['message'] ?? 'no details given.'),
                default => 'Could not reach the SARIMA forecasting service. It may not be running.',
            };

            throw new ForecastServiceUnavailableException($message);
        }

        return WeatherForecast::create([
            'metric' => $metric,
            'horizon' => $horizon,
            'forecast_points' => $result['forecast'] ?? [],
            'diagnostics' => $result['diagnostics'] ?? null,
            'is_sample_based' => $isSampleBased,
            'generated_by' => $requestedBy,
            'generated_at' => now(),
        ]);
    }

    /**
     * Median gap in days between consecutive readings -- inferred from the
     * actual data rather than stored as a static per-station column, so it
     * can't go stale if import cadence for a station ever changes.
     */
    private function computeDaysPerPeriod(array $dates): int
    {
        if (count($dates) < 2) {
            return 1;
        }

        $gaps = [];
        for ($i = 1; $i < count($dates); $i++) {
            $gaps[] = Carbon::parse($dates[$i - 1])->diffInDays(Carbon::parse($dates[$i]));
        }

        sort($gaps);
        $count = count($gaps);
        $mid = intdiv($count, 2);
        $median = $count % 2 === 0 ? ($gaps[$mid - 1] + $gaps[$mid]) / 2 : $gaps[$mid];

        return max(1, (int) round($median));
    }
}
