<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvacuationCenterFacilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'facility_type' => $this->facility_type,
            'quantity' => $this->quantity,
            'is_available' => (bool) $this->is_available,
            'concerns_and_needs' => $this->concerns_and_needs,
            'recorded_at' => $this->recorded_at,
        ];
    }
}
