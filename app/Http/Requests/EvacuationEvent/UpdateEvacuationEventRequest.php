<?php

namespace App\Http\Requests\EvacuationEvent;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvacuationEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'event_type' => ['sometimes', 'in:typhoon,flood,volcanic_eruption,earthquake,other'],
            'typhoon_category' => ['nullable', 'string', 'max:30'],
            'max_wind_speed_kph' => ['nullable', 'numeric', 'min:0'],
            'rainfall_mm' => ['nullable', 'numeric', 'min:0'],
            'alert_level' => ['nullable', 'string', 'max:30'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'in:monitoring,active,closed'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
