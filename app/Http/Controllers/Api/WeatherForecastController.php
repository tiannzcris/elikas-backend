<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ForecastServiceUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Weather\ForecastRequest;
use App\Http\Resources\WeatherForecastResource;
use App\Http\Resources\WeatherReadingResource;
use App\Models\SystemLog;
use App\Models\WeatherForecast;
use App\Models\WeatherReading;
use App\Services\WeatherForecastService;
use Illuminate\Http\Request;

class WeatherForecastController extends Controller
{
    public function readings(Request $request)
    {
        $query = WeatherReading::query()->orderByDesc('reading_date');

        if ($request->filled('station')) {
            $query->where('station', $request->string('station'));
        }

        $readings = $query->paginate($request->integer('per_page', 30));

        return $this->success(WeatherReadingResource::collection($readings)->response()->getData(true));
    }

    public function index(Request $request)
    {
        $forecasts = WeatherForecast::query()
            ->with('generator')
            ->latest('generated_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(WeatherForecastResource::collection($forecasts)->response()->getData(true));
    }

    public function store(ForecastRequest $request, WeatherForecastService $service)
    {
        $validated = $request->validated();

        try {
            $forecast = $service->forecast(
                $validated['metric'],
                $validated['horizon'] ?? 14,
                $validated['station'] ?? null,
                $request->user()->id
            );
        } catch (\RuntimeException $e) {
            // Catches ForecastServiceUnavailableException too, since it
            // extends \RuntimeException.
            // Two different failure classes need two different HTTP codes:
            // not enough local data is a 422 the user fixes by importing
            // more readings (matches PredictiveAnalyticsController's exact
            // pattern); the SARIMA service being unreachable/unconfigured
            // is a 503 -- a deployment problem, not something correctable
            // by changing the request.
            $code = $e instanceof ForecastServiceUnavailableException ? 503 : 422;

            return $this->error($e->getMessage(), $code);
        }

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'weather_forecast.generated',
            'description' => "{$request->user()->name} generated a SARIMA forecast for {$forecast->metric} ({$forecast->horizon} periods).",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            new WeatherForecastResource($forecast->load('generator')),
            'Forecast generated successfully.',
            201
        );
    }
}
