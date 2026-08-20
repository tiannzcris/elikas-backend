<?php

namespace App\Http\Requests\EvacuationCenter;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvacuationCenterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Role-restricted at the route level (administrator, cswd_personnel,
        // barangay_official). barangay_official is further scoped to their
        // own barangay in the controller -- the client-submitted barangay_id
        // is never trusted for that role, so no barangay-specific check
        // belongs here in the request itself.
        return true;
    }

    public function rules(): array
    {
        return [
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:school,covered_court,church,barangay_hall,gymnasium,other'],
            'address' => ['required', 'string'],
            // Optional: not every center has a confirmed map location yet.
            // required_with each other so a half-set coordinate pair (one
            // without the other) is rejected rather than silently accepted.
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'capacity_families' => ['nullable', 'integer', 'min:0'],
            'capacity_persons' => ['nullable', 'integer', 'min:0'],
            'camp_manager_name' => ['nullable', 'string', 'max:150'],
            'camp_manager_contact' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,full,closed,on_standby'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
