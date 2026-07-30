<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvacuationEvent\StoreEvacuationEventRequest;
use App\Http\Requests\EvacuationEvent\UpdateEvacuationEventRequest;
use App\Http\Resources\EvacuationEventResource;
use App\Models\EvacuationEvent;
use App\Models\SystemLog;

class EvacuationEventController extends Controller
{
    /**
     * Returns ALL events regardless of status -- deliberately not filtered
     * to active-only anymore. This single endpoint feeds three different
     * consumers with different needs: the family registration form (which
     * filters client-side to non-closed events, since registering into an
     * already-closed disaster doesn't make sense), and the DROMIC
     * reports / predictive analytics pages (which need closed events
     * visible too, since those are usually generated FOR a completed
     * event). Filtering server-side to "active only" broke the second
     * group of consumers, which is exactly the bug this fixes.
     */
    public function index()
    {
        return $this->success(
            EvacuationEventResource::collection(
                EvacuationEvent::orderByDesc('start_date')->get()
            )
        );
    }

    public function show(EvacuationEvent $evacuationEvent)
    {
        return $this->success(new EvacuationEventResource($evacuationEvent));
    }

    public function store(StoreEvacuationEventRequest $request)
    {
        $event = EvacuationEvent::create($request->validated());

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'evacuation_event.created',
            'description' => "{$request->user()->name} created disaster event \"{$event->name}\".",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(new EvacuationEventResource($event), 'Disaster event created successfully.', 201);
    }

    /**
     * Also how an event gets closed out -- there's no separate "close"
     * endpoint, closing is just setting status to 'closed' (and usually
     * end_date) through this same update.
     */
    public function update(UpdateEvacuationEventRequest $request, EvacuationEvent $evacuationEvent)
    {
        $validated = $request->validated();
        $wasActive = $evacuationEvent->status !== 'closed';

        $evacuationEvent->update($validated);

        if ($wasActive && $evacuationEvent->status === 'closed') {
            SystemLog::create([
                'user_id' => $request->user()->id,
                'action' => 'evacuation_event.closed',
                'description' => "{$request->user()->name} closed out disaster event \"{$evacuationEvent->name}\".",
                'ip_address' => $request->ip(),
            ]);
        }

        return $this->success(new EvacuationEventResource($evacuationEvent->fresh()), 'Disaster event updated successfully.');
    }
}
