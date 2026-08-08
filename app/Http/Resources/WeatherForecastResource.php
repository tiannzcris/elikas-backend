<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeatherForecastResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'metric' => $this->metric,
            'horizon' => $this->horizon,
            'forecast_points' => $this->forecast_points,
            'diagnostics' => $this->diagnostics,
            // Never let the frontend present this run as a real forecast
            // without checking this flag first -- true whenever any reading
            // used to train it was sample/test data, not real PAGASA data.
            'is_sample_based' => (bool) $this->is_sample_based,
            'generated_by' => $this->whenLoaded('generator', fn () => $this->generator?->name),
            'generated_at' => $this->generated_at,
        ];
    }
}
