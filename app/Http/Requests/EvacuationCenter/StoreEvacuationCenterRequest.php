<?php

namespace App\Http\Requests\EvacuationCenter;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvacuationCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role-restricted at the route level (administrator, cswd_personnel
        // only) -- evacuation centers are city infrastructure, not something
        // barangay officials maintain themselves.
        return true;
    }

    public function rules(): array
    {
        return [
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:school,covered_court,church,barangay_hall,gymnasium,other'],
            'address' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'capacity_families' => ['nullable', 'integer', 'min:0'],
            'capacity_persons' => ['nullable', 'integer', 'min:0'],
            'camp_manager_name' => ['nullable', 'string', 'max:150'],
            'camp_manager_contact' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,full,closed,on_standby'],
        ];
    }
}
