<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvacuationCenterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay ? [
                'id' => $this->barangay->id,
                'name' => $this->barangay->name,
            ] : null),
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'capacity_families' => $this->capacity_families,
            'capacity_persons' => $this->capacity_persons,
            'current_occupancy' => $this->currentOccupancy(),
            'occupancy_percent' => $this->occupancyPercent(),
            'camp_manager_name' => $this->camp_manager_name,
            'camp_manager_contact' => $this->camp_manager_contact,
            'status' => $this->status,
            'facilities' => EvacuationCenterFacilityResource::collection($this->whenLoaded('facilities')),
        ];
    }
}
