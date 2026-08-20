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
            // Optional: not every center has a confirmed map location yet.
            // required_with each other so a half-set coordinate pair (one
            // without the other) is rejected rather than silently accepted.
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'capacity_families' => ['nullable', 'integer', 'min:0'],
            'capacity_persons' => ['nullable', 'integer', 'min:0'],
            'camp_manager_name' => ['nullable', 'string', 'max:150'],
            'camp_manager_contact' => ['nullable', 'string', 'max:20'],
            'status' => ['sometimes', 'in:active,full,closed,on_standby'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
