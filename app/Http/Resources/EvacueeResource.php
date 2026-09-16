<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvacueeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'family_id' => $this->family_id,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'sex' => $this->sex,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'age' => $this->age,
            'age_bracket' => $this->age_bracket,
            'civil_status' => $this->civil_status,
            'contact_number' => $this->contact_number,
            'sectoral' => [
                'is_pwd' => (bool) $this->is_pwd,
                'pwd_type' => $this->pwd_type,
                'is_pregnant' => (bool) $this->is_pregnant,
                'is_lactating' => (bool) $this->is_lactating,
                'is_solo_parent' => (bool) $this->is_solo_parent,
                'is_indigenous_person' => (bool) $this->is_indigenous_person,
                'is_4ps_beneficiary' => (bool) $this->is_4ps_beneficiary,
            ],
            'status' => $this->status,
            // True while any of first_name/last_name/sex/date_of_birth is
            // still unset -- created via the fast headcount-only
            // registration path and awaiting "Add details".
            'is_placeholder' => $this->is_placeholder,
            // Only present when the caller eager-loaded family.barangay
            // (currently just the Evacuees page's global search dropdown,
            // via EvacueeController::index()) -- a hint of which barangay
            // this result belongs to, without exposing the whole family.
            'barangay_name' => $this->whenLoaded('family', fn () => $this->family->barangay?->name),
            'evacuation_records' => EvacuationRecordResource::collection($this->whenLoaded('evacuationRecords')),
        ];
    }
}
