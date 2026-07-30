<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvacuationEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'event_type' => $this->event_type,
            'typhoon_category' => $this->typhoon_category,
            'max_wind_speed_kph' => $this->max_wind_speed_kph !== null ? (float) $this->max_wind_speed_kph : null,
            'rainfall_mm' => $this->rainfall_mm !== null ? (float) $this->rainfall_mm : null,
            'alert_level' => $this->alert_level,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'status' => $this->status,
            'description' => $this->description,
            // Live counts, not stored figures -- always reflect actual
            // registered data at the moment the event is viewed.
            'total_families_displaced' => $this->totalFamiliesDisplaced(),
            'total_persons_displaced' => $this->totalPersonsDisplaced(),
            'created_at' => $this->created_at,
        ];
    }
}
