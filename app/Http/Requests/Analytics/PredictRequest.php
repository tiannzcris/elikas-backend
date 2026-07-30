<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class PredictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rainfall_mm' => ['required', 'numeric', 'min:0'],
            'wind_speed_kph' => ['required', 'numeric', 'min:0'],
            // Optional: attach this forecast to a specific ongoing/upcoming
            // event, so the prediction shows up alongside that event's
            // records rather than as a standalone what-if scenario.
            'evacuation_event_id' => ['nullable', 'integer', 'exists:evacuation_events,id'],
        ];
    }
}
