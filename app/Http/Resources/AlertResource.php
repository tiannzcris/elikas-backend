<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evacuation_event' => $this->whenLoaded('evacuationEvent', fn () => $this->evacuationEvent ? [
                'id' => $this->evacuationEvent->id,
                'name' => $this->evacuationEvent->name,
            ] : null),
            'sender' => $this->whenLoaded('sender', fn () => $this->sender ? [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
            ] : null),
            'title' => $this->title,
            'message' => $this->message,
            'alert_type' => $this->alert_type,
            'severity' => $this->severity,
            'status' => $this->status,
            'date_sent' => $this->date_sent,
            'created_at' => $this->created_at,
            // Delivery summary: how many SMS recipients actually succeeded
            // vs failed vs never got attempted (e.g. Semaphore not configured).
            // Only computed when 'recipients' was eager-loaded, to avoid an
            // extra query on every row of a list.
            'recipient_summary' => $this->whenLoaded('recipients', fn () => [
                'total' => $this->recipients->count(),
                'sent' => $this->recipients->where('status', 'sent')->count(),
                'failed' => $this->recipients->where('status', 'failed')->count(),
                'pending' => $this->recipients->where('status', 'pending')->count(),
            ]),
        ];
    }
}
