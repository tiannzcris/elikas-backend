<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesBarangayAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\EvacueeResource;
use App\Models\Evacuee;
use App\Models\EvacuationRecord;
use App\Models\Family;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EvacueeController extends Controller
{
    use AuthorizesBarangayAccess;

    /**
     * Search/list evacuees -- primarily for the "find an evacuee" screen on
     * the web dashboard (e.g. a relative asking whether someone is at a
     * center). Barangay officials are restricted to their own barangay.
     */
    public function index(Request $request)
    {
        $query = Evacuee::query()->with(['family', 'evacuationRecords' => function ($q) {
            $q->whereNull('date_out')->latest('date_in')->limit(1);
        }]);

        $user = $request->user();
        if ($user->isBarangayOfficial()) {
            $query->where('barangay_id', $user->barangay_id);
        } elseif ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }

        if ($request->filled('search')) {
            $term = '%' . $request->string('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term);
            });
        }

        $evacuees = $query->latest()->paginate($request->integer('per_page', 20));

        return $this->success(EvacueeResource::collection($evacuees)->response()->getData(true));
    }

    /**
     * Add a member to an ALREADY-registered family -- e.g. a relative who
     * arrives at the center a day after the rest of the household. Reuses
     * the family's existing evacuation_event/barangay; checks the new
     * member into whichever center/displacement_type is given.
     */
    public function addMember(Request $request, Family $family)
    {
        if (! $this->userMayAccessBarangay($request->user(), $family->barangay_id)) {
            return $this->error('You may not modify families outside your barangay.', 403);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'sex' => ['required', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'civil_status' => ['nullable', 'in:single,married,widowed,separated,divorced'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'is_pwd' => ['boolean'],
            'pwd_type' => ['nullable', 'required_if:is_pwd,true', 'string', 'max:100'],
            'is_pregnant' => ['boolean'],
            'is_lactating' => ['boolean'],
            'is_solo_parent' => ['boolean'],
            'is_indigenous_person' => ['boolean'],
            'is_4ps_beneficiary' => ['boolean'],
            'evacuation_center_id' => ['nullable', 'integer', 'exists:evacuation_centers,id'],
            'displacement_type' => ['required', 'in:inside_center,outside_center'],
        ]);

        $evacuee = Evacuee::create([
            'family_id' => $family->id,
            'barangay_id' => $family->barangay_id,
            ...collect($validated)->except(['evacuation_center_id', 'displacement_type'])->toArray(),
            'status' => 'active',
        ]);

        EvacuationRecord::create([
            'evacuee_id' => $evacuee->id,
            'evacuation_center_id' => $validated['evacuation_center_id'] ?? null,
            'evacuation_event_id' => $family->evacuation_event_id,
            'displacement_type' => $validated['displacement_type'],
            'date_in' => now(),
            'status' => 'currently_evacuated',
        ]);

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'evacuee.added',
            'description' => "{$request->user()->name} added {$evacuee->full_name} to family #{$family->id}.",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(new EvacueeResource($evacuee->load('evacuationRecords')), 'Member added successfully.', 201);
    }

    public function update(Request $request, Evacuee $evacuee)
    {
        if (! $this->userMayAccessBarangay($request->user(), $evacuee->barangay_id)) {
            return $this->error('You may not modify evacuees outside your barangay.', 403);
        }

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'suffix' => ['nullable', 'string', 'max:10'],
            'civil_status' => ['nullable', 'in:single,married,widowed,separated,divorced'],
            'contact_number' => ['nullable', 'string', 'max:20'],
            'is_pwd' => ['sometimes', 'boolean'],
            'pwd_type' => ['nullable', 'string', 'max:100'],
            'is_pregnant' => ['sometimes', 'boolean'],
            'is_lactating' => ['sometimes', 'boolean'],
            'is_solo_parent' => ['sometimes', 'boolean'],
            'is_indigenous_person' => ['sometimes', 'boolean'],
            'is_4ps_beneficiary' => ['sometimes', 'boolean'],
        ]);

        $evacuee->update($validated);

        return $this->success(new EvacueeResource($evacuee->fresh()), 'Evacuee updated successfully.');
    }

    /**
     * Closes out the evacuee's currently-active evacuation record -- either
     * because they went home, or were transferred elsewhere. This is what
     * moves them out of the "currently displaced" DROMIC counts.
     */
    public function checkOut(Request $request, Evacuee $evacuee)
    {
        if (! $this->userMayAccessBarangay($request->user(), $evacuee->barangay_id)) {
            return $this->error('You may not modify evacuees outside your barangay.', 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['returned_home', 'transferred'])],
        ]);

        $record = $evacuee->evacuationRecords()->whereNull('date_out')->latest('date_in')->first();

        if (! $record) {
            return $this->error('This evacuee has no active evacuation record to close out.', 404);
        }

        $record->update(['date_out' => now(), 'status' => $validated['status']]);
        $evacuee->update(['status' => $validated['status']]);

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'evacuee.checked_out',
            'description' => "{$request->user()->name} marked {$evacuee->full_name} as {$validated['status']}.",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(new EvacueeResource($evacuee->fresh(['evacuationRecords'])), 'Evacuee checked out successfully.');
    }
}
