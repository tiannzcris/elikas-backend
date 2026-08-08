<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wraps the SARIMA/SARIMAX forecasting microservice (sarima-service/, a
 * separate Python/FastAPI process this Laravel app talks to over HTTP --
 * see sarima-service/README.md for what it does and how to run it). Mirrors
 * SemaphoreSmsService's shape deliberately: a student capstone's VPS may not
 * have the Python service deployed/running yet, so this degrades gracefully
 * rather than throwing -- with no URL configured, it logs what WOULD have
 * been requested and returns a clear "not_configured" result the caller can
 * surface, instead of a 500.
 */
class SarimaForecastService
{
    public function forecast(string $metric, array $series, int $horizon, int $daysPerPeriod, ?int $seasonalPeriods = null): array
    {
        $url = config('services.sarima.url');

        if (! $url) {
            Log::info("[SarimaForecastService] No SARIMA service URL configured -- would have requested a {$horizon}-period forecast for {$metric}.");

            return ['success' => false, 'reason' => 'not_configured'];
        }

        try {
            $response = Http::timeout((int) config('services.sarima.timeout', 30))
                ->withHeaders(['X-Service-Token' => config('services.sarima.token')])
                ->post(rtrim($url, '/').'/forecast', [
                    'metric' => $metric,
                    'series' => $series,
                    'horizon' => $horizon,
                    'days_per_period' => $daysPerPeriod,
                    'seasonal_periods' => $seasonalPeriods,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("[SarimaForecastService] SARIMA service returned an error for metric={$metric}: {$response->body()}");

            return ['success' => false, 'reason' => 'service_error', 'details' => $response->json()];
        } catch (\Throwable $e) {
            // Network failure, timeout, unreachable VPS process, etc. --
            // one failed forecast call should never take down the request.
            Log::error("[SarimaForecastService] Exception calling SARIMA service: {$e->getMessage()}");

            return ['success' => false, 'reason' => 'exception'];
        }
    }
}
