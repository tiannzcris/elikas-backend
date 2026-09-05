<?php

namespace App\Http\Requests\HazardProneArea;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHazardProneAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * No 'geojson' rule here, unlike StoreHazardProneAreaRequest -- editing
     * the drawn shape itself isn't supported yet (redraw-and-recreate is
     * the current path for that). This only ever touches the descriptive
     * fields; hazard_prone_areas.geometry and its map_layers.geojson_data
     * are left completely untouched by update().
     */
    public function rules(): array
    {
        return [
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
            'area_name' => ['required', 'string', 'max:150'],
            'hazard_type' => ['required', 'in:flood,landslide,lahar,storm_surge,volcanic_danger_zone'],
            'description' => ['nullable', 'string'],
        ];
    }
}
