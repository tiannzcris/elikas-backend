<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evacuation_event' => $this->whenLoaded('evacuationEvent', fn () => $this->evacuationEvent ? [
                'id' => $this->evacuationEvent->id,
                'name' => $this->evacuationEvent->name,
            ] : null),
            'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay ? [
                'id' => $this->barangay->id,
                'name' => $this->barangay->name,
            ] : null),
            'head_of_family' => $this->whenLoaded('headOfFamily', fn () => $this->headOfFamily ? [
                'id' => $this->headOfFamily->id,
                'full_name' => $this->headOfFamily->full_name,
            ] : null),
            'is_4ps_beneficiary' => (bool) $this->is_4ps_beneficiary,
            // Computed from loaded members -- "does ANY member of this
            // household have this flag", matching the sectoral tags shown
            // per-family on the Evacuees list (distinct from a single
            // member's own individual flags in EvacueeResource).
            'has_pwd_member' => $this->whenLoaded('members', fn () => $this->members->contains('is_pwd', true)),
            'has_senior_member' => $this->whenLoaded('members', fn () => $this->members->contains(fn ($m) => $m->age_bracket === 'senior_citizen')),
            'has_lactating_member' => $this->whenLoaded('members', fn () => $this->members->contains('is_lactating', true)),
            // Not a real column on families (that data lives per-evacuee on
            // evacuation_records) -- this shows ONE representative center,
            // taken from the first member's most recent record, since in
            // practice a whole family registers together at the same
            // center (see FamilyController::store()).
            'evacuation_center' => $this->whenLoaded('members', function () {
                $record = $this->members->first()?->evacuationRecords?->sortByDesc('date_in')->first();

                return $record?->evacuationCenter ? ['id' => $record->evacuationCenter->id, 'name' => $record->evacuationCenter->name] : null;
            }),
            'member_count' => $this->whenCounted('members'),
            'members' => EvacueeResource::collection($this->whenLoaded('members')),
            'created_at' => $this->created_at,
        ];
    }
}
