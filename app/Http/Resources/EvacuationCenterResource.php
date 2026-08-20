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
            'created_by' => $this->created_by,
            'creator' => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay ? [
                'id' => $this->barangay->id,
                'name' => $this->barangay->name,
            ] : null),
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            // Not every center has a confirmed map location yet -- real
            // null, not force-cast to 0.0, so "no location set" (see the
            // amber badge on the centers list) is never confused with
            // "actually located at 0,0".
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
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
