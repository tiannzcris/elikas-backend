<?php

namespace App\Http\Requests\HazardProneArea;

use Illuminate\Foundation\Http\FormRequest;

class StoreHazardProneAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
            'area_name' => ['required', 'string', 'max:150'],
            'hazard_type' => ['required', 'in:flood,landslide,lahar,storm_surge,volcanic_danger_zone'],
            'description' => ['nullable', 'string'],
            // GeoJSON Polygon, exactly what Leaflet.draw produces client-side.
            // A closed ring needs at least 4 coordinate pairs (3 distinct
            // vertices + the repeated closing point).
            'geojson' => ['required', 'array'],
            'geojson.type' => ['required', 'in:Polygon'],
            'geojson.coordinates' => ['required', 'array', 'min:1'],
            'geojson.coordinates.0' => ['required', 'array', 'min:4'],
        ];
    }
}
