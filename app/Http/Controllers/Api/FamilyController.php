<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\AuthorizesBarangayAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\Evacuee\RegisterFamilyRequest;
use App\Http\Resources\FamilyResource;
use App\Models\Evacuee;
use App\Models\EvacuationRecord;
use App\Models\Family;
use App\Models\SystemLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FamilyController extends Controller
{
    use AuthorizesBarangayAccess;

    /**
     * Register a household arriving at (or displaced outside) an evacuation
     * center: creates the family, every member as an evacuee, marks the head
     * of family, and checks each member into the given event/center --
     * all in one transaction, since a partially-created family (e.g. the
     * family row exists but a member failed validation halfway through)
     * would corrupt the DROMIC counts.
     */
    public function store(RegisterFamilyRequest $request)
    {
        $validated = $request->validated();

        // Deliberately NOT restricted to the registering staff member's own
        // barangay -- an evacuee's home barangay can genuinely differ from
        // whichever barangay's center/staff happens to be registering them
        // (e.g. displaced to a center outside their own barangay). Viewing
        // an existing family's details (show(), below) still stays
        // restricted to a barangay official's own barangay -- that's a
        // separate, unrelated scope question from who they can register.

        $family = DB::transaction(function () use ($validated, $request) {
            $family = Family::create([
                'evacuation_event_id' => $validated['evacuation_event_id'],
                'barangay_id' => $validated['barangay_id'],
                'home_address' => $validated['home_address'] ?? null,
                'is_4ps_beneficiary' => $validated['is_4ps_beneficiary'] ?? false,
            ]);

            $headOfFamilyId = null;

            foreach ($validated['members'] as $member) {
                $evacuee = Evacuee::create([
                    'family_id' => $family->id,
                    'barangay_id' => $validated['barangay_id'],
                    'first_name' => $member['first_name'],
                    'middle_name' => $member['middle_name'] ?? null,
                    'last_name' => $member['last_name'],
                    'suffix' => $member['suffix'] ?? null,
                    'sex' => $member['sex'],
                    'date_of_birth' => $member['date_of_birth'],
                    'civil_status' => $member['civil_status'] ?? null,
                    'contact_number' => $member['contact_number'] ?? null,
                    'is_pwd' => $member['is_pwd'] ?? false,
                    'pwd_type' => $member['pwd_type'] ?? null,
                    'is_pregnant' => $member['is_pregnant'] ?? false,
                    'is_lactating' => $member['is_lactating'] ?? false,
                    'is_solo_parent' => $member['is_solo_parent'] ?? false,
                    'is_indigenous_person' => $member['is_indigenous_person'] ?? false,
                    'is_4ps_beneficiary' => $member['is_4ps_beneficiary'] ?? false,
                    'status' => 'active',
                ]);

                EvacuationRecord::create([
                    'evacuee_id' => $evacuee->id,
                    'evacuation_center_id' => $validated['evacuation_center_id'] ?? null,
                    'evacuation_event_id' => $validated['evacuation_event_id'],
                    'displacement_type' => $validated['displacement_type'],
                    'date_in' => now(),
                    'status' => 'currently_evacuated',
                ]);

                if (! empty($member['is_head_of_family'])) {
                    $headOfFamilyId = $evacuee->id;
                }
            }

            $family->update(['head_of_family_evacuee_id' => $headOfFamilyId]);

            SystemLog::create([
                'user_id' => $request->user()->id,
                'action' => 'family.registered',
                'description' => sprintf(
                    '%s registered a family of %d member(s) in barangay #%d for event #%d.',
                    $request->user()->name,
                    count($validated['members']),
                    $validated['barangay_id'],
                    $validated['evacuation_event_id']
                ),
                'ip_address' => $request->ip(),
            ]);

            return $family;
        });

        return $this->success(
            new FamilyResource(
                $family->load(['members.evacuationRecords.evacuationCenter', 'headOfFamily', 'barangay', 'evacuationEvent'])
            ),
            'Family registered successfully.',
            201
        );
    }

    /**
     * List families, optionally filtered by barangay or event. Barangay
     * officials only ever see their own barangay's families, regardless of
     * what filter they pass -- enforced here, not just left to the client.
     * Defaults to CURRENT state only (non-closed events) when no specific
     * event is requested -- see scopeToRequest().
     */
    public function index(Request $request)
    {
        // headOfFamily was missing here before -- store()/show() both
        // loaded it, but this list endpoint didn't, meaning "Head of
        // family" has been silently blank on the Evacuees list this whole
        // time. members.evacuationRecords.evacuationCenter is needed too --
        // evacuation_center isn't a column on families itself (it lives per-
        // evacuee, on evacuation_records), so showing one representative
        // center per family requires this deeper chain, same pattern
        // store()/show() already use.
        $query = Family::query()->withCount('members')
            ->with(['barangay', 'evacuationEvent', 'headOfFamily', 'members.evacuationRecords.evacuationCenter']);

        $this->scopeToRequest($query, $request);

        $families = $query->latest()->paginate($request->integer('per_page', 20));

        return $this->success(FamilyResource::collection($families)->response()->getData(true));
    }

    /**
     * Lightweight aggregate counts for stat cards (Dashboard's "Total
     * evacuees", the Evacuees page's "Households"/"Total persons") --
     * computed via direct count queries rather than fetching/paginating
     * full family records, so these numbers are never silently truncated
     * by whatever page size the list view underneath happens to use (the
     * bug that produced 200 households / 709 persons when the real
     * current-state totals were 15 / 52). Same scoping as index(), so the
     * cards and the table they sit above always agree with each other.
     */
    public function stats(Request $request)
    {
        $query = Family::query();
        $this->scopeToRequest($query, $request);

        $familyIds = $query->pluck('id');

        return $this->success([
            'households' => $familyIds->count(),
            'total_persons' => Evacuee::whereIn('family_id', $familyIds)->count(),
        ]);
    }

    public function show(Request $request, Family $family)
    {
        if (! $this->userMayAccessBarangay($request->user(), $family->barangay_id)) {
            return $this->error('You may not view families outside your barangay.', 403);
        }

        return $this->success(
            new FamilyResource(
                $family->load(['members.evacuationRecords.evacuationCenter', 'headOfFamily', 'barangay', 'evacuationEvent'])
            )
        );
    }

    /**
     * Reassigns which evacuation center this family is checked into --
     * e.g. their original center filled up, or a placeholder/sample center
     * is being swapped for a real verified one. evacuation_center_id lives
     * per-evacuee on evacuation_records (not on Family itself), so this
     * updates every member's currently-active record (date_out still null)
     * in one bulk update -- matches the same "whole family, one center"
     * assumption FamilyController::store() and FamilyResource already make.
     * Members who already checked out are left untouched.
     */
    public function updateEvacuationCenter(Request $request, Family $family)
    {
        if (! $this->userMayAccessBarangay($request->user(), $family->barangay_id)) {
            return $this->error('You may not modify families outside your barangay.', 403);
        }

        $validated = $request->validate([
            'evacuation_center_id' => ['required', 'integer', 'exists:evacuation_centers,id'],
        ]);

        $memberIds = $family->members()->pluck('id');

        $updated = EvacuationRecord::whereIn('evacuee_id', $memberIds)
            ->whereNull('date_out')
            ->update([
                'evacuation_center_id' => $validated['evacuation_center_id'],
                'displacement_type' => 'inside_center',
            ]);

        if ($updated === 0) {
            return $this->error(
                'This family has no active evacuation records to reassign -- every member has already checked out.',
                422
            );
        }

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'family.evacuation_center_reassigned',
            'description' => sprintf(
                '%s reassigned family #%d (%d active member(s)) to evacuation center #%d.',
                $request->user()->name,
                $family->id,
                $updated,
                $validated['evacuation_center_id']
            ),
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            new FamilyResource(
                $family->fresh()->load(['members.evacuationRecords.evacuationCenter', 'headOfFamily', 'barangay', 'evacuationEvent'])
            ),
            'Evacuation center updated successfully.'
        );
    }

    /**
     * Shared by index() and stats(). Barangay officials only ever see
     * their own barangay's families, regardless of what filter they pass.
     * When no specific evacuation_event_id is requested, defaults to
     * CURRENT state only (events with status != 'closed') rather than an
     * all-time count spanning every historical closed event ever seeded
     * -- an explicit evacuation_event_id (e.g. the evacuation-events
     * detail page asking for one specific event's families) still always
     * gets exactly that event, closed or not, since the caller asked for
     * it by id, not by browsing "what's current".
     */
    private function scopeToRequest(Builder $query, Request $request): void
    {
        $user = $request->user();
        if ($user->isBarangayOfficial()) {
            $query->where('barangay_id', $user->barangay_id);
        } elseif ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }

        if ($request->filled('evacuation_event_id')) {
            $query->where('evacuation_event_id', $request->integer('evacuation_event_id'));
        } else {
            $query->whereHas('evacuationEvent', fn (Builder $q) => $q->where('status', '!=', 'closed'));
        }
    }
}
