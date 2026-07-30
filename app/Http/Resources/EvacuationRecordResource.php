<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EvacuationRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evacuation_center' => $this->whenLoaded('evacuationCenter', fn () => $this->evacuationCenter ? [
                'id' => $this->evacuationCenter->id,
                'name' => $this->evacuationCenter->name,
            ] : null),
            'displacement_type' => $this->displacement_type,
            'date_in' => $this->date_in,
            'date_out' => $this->date_out,
            'status' => $this->status,
        ];
    }
}
