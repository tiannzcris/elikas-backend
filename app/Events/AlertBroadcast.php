<?php

namespace App\Events;

use App\Models\Alert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Deliberately implements ShouldBroadcastNow, not ShouldBroadcast.
 *
 * ShouldBroadcast queues the actual send as a job on the 'jobs' database
 * table by default -- which this project doesn't have (it was bundled in
 * Laravel's default migration alongside 'sessions'/'cache', both deleted
 * back in Step 1 for the same reason). ShouldBroadcastNow sends immediately,
 * in the same request, with no queue or extra table required -- the right
 * tradeoff here since alert volume is low and instant delivery is the point.
 *
 * Broadcasts on a plain public Channel (not Private/Presence), since alert
 * content isn't sensitive and this avoids needing channel-authorization
 * logic in routes/channels.php for this step.
 */
class AlertBroadcast implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Alert $alert) {}

    public function broadcastOn(): array
    {
        return [new Channel('dashboard-alerts')];
    }

    public function broadcastAs(): string
    {
        return 'alert.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->alert->id,
            'title' => $this->alert->title,
            'message' => $this->alert->message,
            'alert_type' => $this->alert->alert_type,
            'created_at' => $this->alert->created_at?->toIso8601String(),
        ];
    }
}
