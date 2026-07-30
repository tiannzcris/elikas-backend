<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evacuation_event' => $this->whenLoaded('evacuationEvent', fn () => $this->evacuationEvent ? [
                'id' => $this->evacuationEvent->id,
                'name' => $this->evacuationEvent->name,
            ] : null),
            'report_type' => $this->report_type,
            'file_format' => $this->file_format,
            'generated_by' => $this->whenLoaded('generator', fn () => $this->generator?->name),
            'generated_at' => $this->generated_at,
            'download_url' => route('reports.download', $this->id),
        ];
    }
}
