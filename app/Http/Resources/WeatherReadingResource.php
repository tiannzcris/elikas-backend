<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeatherReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reading_date' => $this->reading_date?->format('Y-m-d'),
            'station' => $this->station,
            'rainfall_mm' => $this->rainfall_mm !== null ? (float) $this->rainfall_mm : null,
            'wind_speed_kph' => $this->wind_speed_kph !== null ? (float) $this->wind_speed_kph : null,
            'is_sample' => (bool) $this->is_sample,
        ];
    }
}
