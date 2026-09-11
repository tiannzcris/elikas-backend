<?php

namespace App\Http\Requests\Alert;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Only the content fields -- editing an alert never re-triggers SMS,
     * so nothing here touches status/date_sent, and there's no
     * evacuee_id/notify_evacuees/notify_barangay_officials/barangay_id
     * (those only ever make sense at send time, against
     * AlertController::store()'s recipient-building logic, not on an
     * already-sent alert's recipients, which are left untouched).
     */
    public function rules(): array
    {
        return [
            'evacuation_event_id' => ['nullable', 'integer', 'exists:evacuation_events,id'],
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:1000'],
            'alert_type' => ['required', 'in:typhoon,flood,volcanic,earthquake,general_advisory'],
            'severity' => ['required', 'in:mandatory,advisory,info,all_clear'],
        ];
    }
}
