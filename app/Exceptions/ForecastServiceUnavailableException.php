<?php

namespace App\Exceptions;

/**
 * Thrown when the local weather_readings data is sufficient but the SARIMA
 * microservice call itself failed (not configured, unreachable, or returned
 * an error) -- a distinct failure class from "not enough historical data
 * yet" (plain \RuntimeException, mirroring PredictiveAnalyticsService),
 * since the fix for each is completely different: one needs more imported
 * data, the other needs the Python service deployed/running/reachable.
 * WeatherForecastController maps this to a 503, plain \RuntimeException to
 * a 422.
 */
class ForecastServiceUnavailableException extends \RuntimeException
{
    //
}
