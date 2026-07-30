<?php

namespace App\Http\Requests\EvacuationCenter;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvacuationCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barangay_id' => ['sometimes', 'integer', 'exists:barangays,id'],
            'name' => ['sometimes', 'string', 'max:150'],
            'type' => ['sometimes', 'in:school,covered_court,church,barangay_hall,gymnasium,other'],
            'address' => ['sometimes', 'string'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180'],
            'capacity_families' => ['nullable', 'integer', 'min:0'],
            'capacity_persons' => ['nullable', 'integer', 'min:0'],
            'camp_manager_name' => ['nullable', 'string', 'max:150'],
            'camp_manager_contact' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'in:active,full,closed,on_standby'],
        ];
    }
}
