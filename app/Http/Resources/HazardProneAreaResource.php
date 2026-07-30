<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HazardProneAreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay ? [
                'id' => $this->barangay->id,
                'name' => $this->barangay->name,
            ] : null),
            'area_name' => $this->area_name,
            'hazard_type' => $this->hazard_type,
            'description' => $this->description,
            // Pulled from the companion map_layers row, not the raw spatial
            // column -- geojson_data is already in the exact shape Leaflet
            // needs, no WKT-to-GeoJSON conversion required on read.
            'geojson' => $this->whenLoaded('mapLayers', fn () => optional($this->mapLayers->first())->geojson_data),
            'created_at' => $this->created_at,
        ];
    }
}
