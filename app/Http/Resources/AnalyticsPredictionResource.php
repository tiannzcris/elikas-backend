<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyticsPredictionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evacuation_event' => $this->whenLoaded('evacuationEvent', fn () => $this->evacuationEvent ? [
                'id' => $this->evacuationEvent->id,
                'name' => $this->evacuationEvent->name,
            ] : null),
            'predicted_evacuees' => $this->predicted_evacuees,
            'predicted_center_occupancy' => $this->predicted_center_occupancy,
            'predicted_resources_needed' => (float) $this->predicted_resources_needed,
            'model_used' => $this->model_used,
            // Null (not 0) means "not enough historical events yet to
            // evaluate" -- the frontend must not treat null as a score of 0.
            'mae_score' => $this->mae_score !== null ? (float) $this->mae_score : null,
            'r2_score' => $this->r2_score !== null ? (float) $this->r2_score : null,
            'input_payload' => $this->input_payload,
            'generated_at' => $this->generated_at,
        ];
    }
}
