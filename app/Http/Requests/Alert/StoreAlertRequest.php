<?php

namespace App\Http\Requests\Alert;

use Illuminate\Foundation\Http\FormRequest;

class StoreAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evacuation_event_id' => ['nullable', 'integer', 'exists:evacuation_events,id'],
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:1000'],
            'alert_type' => ['required', 'in:typhoon,flood,volcanic,earthquake,general_advisory'],
            'severity' => ['required', 'in:mandatory,advisory,info,all_clear'],
            'notify_barangay_officials' => ['boolean'],
            'notify_evacuees' => ['boolean'],
            // Optional: restrict SMS notification to one barangay's officials/evacuees
            // instead of city-wide -- useful for a localized advisory.
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
        ];
    }
}
