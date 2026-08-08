<?php

namespace App\Http\Requests\Weather;

use Illuminate\Foundation\Http\FormRequest;

class ForecastRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'metric' => ['required', 'in:rainfall_mm,wind_speed_kph'],
            // Capped at 60 -- unlike a cheap DB read, this triggers real
            // SARIMA fitting work on the Python service, so the request
            // size is deliberately bounded.
            'horizon' => ['nullable', 'integer', 'min:1', 'max:60'],
            'station' => ['nullable', 'string', 'max:100'],
        ];
    }
}
