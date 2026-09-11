<?php

namespace App\Http\Controllers\Api;

use App\Events\AlertBroadcast;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alert\StoreAlertRequest;
use App\Http\Requests\Alert\UpdateAlertRequest;
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
     * Editing an alert only ever touches its content (title, message,
     * alert_type, severity, evacuation_event_id) -- never status/date_sent,
     * and never re-runs the recipient-building/SMS-sending logic in
     * store(). Fixing a typo in a mandatory evacuation order shouldn't
     * re-blast everyone who already received the original SMS.
     */
    public function update(UpdateAlertRequest $request, Alert $alert)
    {
        $validated = $request->validated();

        $alert->update([
            'evacuation_event_id' => $validated['evacuation_event_id'] ?? null,
            'title' => $validated['title'],
            'message' => $validated['message'],
            'alert_type' => $validated['alert_type'],
            'severity' => $validated['severity'],
        ]);

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'alert.updated',
            'description' => "{$request->user()->name} edited alert \"{$alert->title}\".",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            new AlertResource($alert->fresh(['sender', 'evacuationEvent', 'recipients'])),
            'Alert updated successfully.'
        );
    }

    /**
     * alert_recipients.alert_id cascades on delete (confirmed directly in
     * its migration) -- no manual recipient cleanup needed here.
     */
    public function destroy(Request $request, Alert $alert)
    {
        $title = $alert->title;
        $alert->delete();

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'alert.deleted',
            'description' => "{$request->user()->name} deleted alert \"{$title}\".",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(null, 'Alert deleted successfully.');
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

        // A specific evacuee_id always wins over notify_evacuees/barangay_id
        // entirely -- deliberately scoping to exactly one person (e.g. a
        // safe test send to one's own registered number) should never
        // silently widen into a barangay/city-wide broadcast because those
        // other fields also happened to be submitted from the same form.
        if (! empty($validated['evacuee_id'])) {
            $evacuee = Evacuee::find($validated['evacuee_id']);

            $recipientCount += $this->smsRecipients(
                $alert,
                $sms,
                ($evacuee && $evacuee->contact_number) ? [$evacuee->id => $evacuee->contact_number] : [],
                'resident_sms'
            );
        } elseif (! empty($validated['notify_evacuees'])) {
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

            // $result['reason'] is 'not_configured' | 'api_error' | 'exception'
            // (absent entirely on success). $result['detail'] -- the raw
            // Semaphore response body, or the exception message -- is only
            // present for api_error/exception specifically, per
            // SemaphoreSmsService::send(). Concatenating both (when detail
            // exists) means a future real failure is actually diagnosable
            // from this one column, not just "failed" with no context.
            $failureReason = null;
            if (! $result['success']) {
                $failureReason = isset($result['detail'])
                    ? "{$result['reason']}: {$result['detail']}"
                    : $result['reason'];
            }

            AlertRecipient::create([
                'alert_id' => $alert->id,
                'recipient_type' => $recipientType,
                'recipient_value' => $phoneNumber,
                'status' => $result['success'] ? 'sent' : 'failed',
                'failure_reason' => $failureReason,
                'date_sent' => $result['success'] ? now() : null,
            ]);

            $count++;
        }

        return $count;
    }
}
