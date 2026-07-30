<?php

namespace App\Http\Controllers\Api;

use App\Events\AlertBroadcast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alert\StoreAlertRequest;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Models\AlertRecipient;
use App\Models\Evacuee;
use App\Models\SystemLog;
use App\Models\User;
use App\Services\SemaphoreSmsService;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $alerts = Alert::query()
            ->with(['sender', 'evacuationEvent', 'recipients'])
            ->latest('created_at')
            ->paginate($request->integer('per_page', 20));

        return $this->success(AlertResource::collection($alerts)->response()->getData(true));
    }

    public function show(Alert $alert)
    {
        return $this->success(
            new AlertResource($alert->load(['sender', 'evacuationEvent', 'recipients']))
        );
    }

    /**
     * Creates an alert, broadcasts it to the live dashboard instantly, and
     * -- if requested -- attempts SMS delivery to barangay officials and/or
     * registered evacuees with a phone number on file. SMS delivery is
     * best-effort: a failed or unconfigured SMS gateway never blocks the
     * alert itself from being created and broadcast.
     */
    public function store(StoreAlertRequest $request, SemaphoreSmsService $sms)
    {
        $validated = $request->validated();

        $alert = Alert::create([
            'evacuation_event_id' => $validated['evacuation_event_id'] ?? null,
            'sent_by' => $request->user()->id,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'alert_type' => $validated['alert_type'],
            'severity' => $validated['severity'],
            'status' => 'draft',
        ]);

        // Real-time dashboard delivery -- instant, free, no external service.
        broadcast(new AlertBroadcast($alert));

        $recipientCount = 0;

        if (! empty($validated['notify_barangay_officials'])) {
            $recipientCount += $this->smsRecipients(
                $alert,
                $sms,
                User::whereHas('role', fn ($q) => $q->where('name', 'barangay_official'))
                    ->whereNotNull('contact_number')
                    ->when(! empty($validated['barangay_id']), fn ($q) => $q->where('barangay_id', $validated['barangay_id']))
                    ->pluck('contact_number', 'id'),
                'barangay_official'
            );
        }

        if (! empty($validated['notify_evacuees'])) {
            $recipientCount += $this->smsRecipients(
                $alert,
                $sms,
                Evacuee::where('status', 'active')
                    ->whereNotNull('contact_number')
                    ->when(! empty($validated['barangay_id']), fn ($q) => $q->where('barangay_id', $validated['barangay_id']))
                    ->pluck('contact_number', 'id'),
                'resident_sms'
            );
        }

        $alert->update(['status' => 'sent', 'date_sent' => now()]);

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'alert.sent',
            'description' => "{$request->user()->name} sent alert \"{$alert->title}\" to {$recipientCount} SMS recipient(s), plus the live dashboard.",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            new AlertResource($alert->fresh(['sender', 'evacuationEvent', 'recipients'])),
            'Alert sent successfully.',
            201
        );
    }

    /**
     * Sends SMS to a list of [id => phone_number] pairs, recording one
     * alert_recipients row per attempt regardless of outcome, so delivery
     * status is auditable even when Semaphore isn't configured yet.
     */
    private function smsRecipients(Alert $alert, SemaphoreSmsService $sms, $phoneNumbersById, string $recipientType): int
    {
        $count = 0;

        foreach ($phoneNumbersById as $phoneNumber) {
            if (! $phoneNumber) {
                continue;
            }

            $result = $sms->send($phoneNumber, "{$alert->title}: {$alert->message}");

            AlertRecipient::create([
                'alert_id' => $alert->id,
                'recipient_type' => $recipientType,
                'recipient_value' => $phoneNumber,
                'status' => $result['success'] ? 'sent' : 'failed',
                'date_sent' => $result['success'] ? now() : null,
            ]);

            $count++;
        }

        return $count;
    }
}
