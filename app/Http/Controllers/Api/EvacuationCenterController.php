<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvacuationCenter\StoreEvacuationCenterRequest;
use App\Http\Requests\EvacuationCenter\UpdateEvacuationCenterRequest;
use App\Http\Resources\EvacuationCenterQuickCountResource;
use App\Http\Resources\EvacuationCenterResource;
use App\Http\Resources\FamilyResource;
use App\Models\EvacuationCenter;
use App\Models\EvacuationCenterFacility;
use App\Models\EvacuationCenterQuickCount;
use App\Models\EvacuationRecord;
use App\Models\Evacuee;
use App\Models\Family;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EvacuationCenterController extends Controller
{
    /**
     * Lightweight lookup list for form dropdowns (registration form, etc.)
     * -- kept intentionally cheap (no occupancy calculation) since this is
     * called on every barangay-change in the registration form.
     */
    public function index(Request $request)
    {
        $query = EvacuationCenter::query();

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }

        return $this->success(
            $query->orderBy('name')->get(['id', 'name', 'barangay_id', 'status'])
        );
    }

    /**
     * Full detail view -- includes facilities and live occupancy, unlike
     * index() above. Open to all three staff roles: barangay officials need
     * to see occupancy/facilities to decide where to send an arriving family.
     */
    public function show(EvacuationCenter $evacuationCenter)
    {
        return $this->success(
            new EvacuationCenterResource($evacuationCenter->load(['barangay', 'facilities', 'creator']))
        );
    }

    public function store(StoreEvacuationCenterRequest $request)
    {
        $validated = $request->validated();
        $user = $request->user();

        // A barangay official may only ever create a center in their own
        // barangay -- the client-submitted barangay_id is never trusted for
        // this role, same principle as AuthorizesBarangayAccess elsewhere.
        // Administrator/CSWD accounts keep full access to set any barangay.
        if ($user->isBarangayOfficial()) {
            $validated['barangay_id'] = $user->barangay_id;
        }

        $validated['created_by'] = $user->id;

        // 'photo' is the uploaded file itself, not a DB column -- pulled
        // out and stored separately, with the resulting relative path
        // saved to photo_path instead.
        unset($validated['photo']);
        if ($request->hasFile('photo')) {
            $validated['photo_path'] = $request->file('photo')->store('evacuation-centers', 'public');
        }

        $center = DB::transaction(function () use ($validated, $request) {
            $center = EvacuationCenter::create($validated);

            SystemLog::create([
                'user_id' => $request->user()->id,
                'action' => 'evacuation_center.created',
                'description' => "{$request->user()->name} created evacuation center '{$center->name}'.",
                'ip_address' => $request->ip(),
            ]);

            return $center;
        });

        return $this->success(
            new EvacuationCenterResource($center->fresh(['barangay', 'facilities', 'creator'])),
            'Evacuation center created successfully.',
            201
        );
    }

    public function update(UpdateEvacuationCenterRequest $request, EvacuationCenter $evacuationCenter)
    {
        $user = $request->user();

        // Administrator/CSWD may edit any center. A barangay official may
        // only edit centers they themselves created -- checked against the
        // actual creator, not just a barangay match, since two different
        // officials could serve the same barangay over time.
        if ($user->isBarangayOfficial() && $evacuationCenter->created_by !== $user->id) {
            return $this->error('You may only edit evacuation centers you created yourself.', 403);
        }

        $validated = $request->validated();

        // Same barangay lock as store() -- editing is not a loophole to
        // reassign a center to a different barangay.
        if ($user->isBarangayOfficial()) {
            $validated['barangay_id'] = $user->barangay_id;
        }

        // 'photo' is the uploaded file itself, not a DB column. Only
        // touched when a NEW file is actually sent -- no 'photo' in the
        // request just leaves the existing photo_path alone, same pattern
        // as UpdateUserRequest's "blank password means keep the current
        // one". Replacing an existing photo deletes the old file so
        // storage doesn't accumulate orphaned uploads nothing references.
        unset($validated['photo']);
        if ($request->hasFile('photo')) {
            if ($evacuationCenter->photo_path) {
                Storage::disk('public')->delete($evacuationCenter->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('evacuation-centers', 'public');
        }

        $evacuationCenter->update($validated);

        return $this->success(
            new EvacuationCenterResource($evacuationCenter->fresh(['barangay', 'facilities', 'creator'])),
            'Evacuation center updated successfully.'
        );
    }

    /**
     * Find the N nearest active/on-standby centers to a given point, using
     * the spatial index instead of pulling every row into PHP and computing
     * distance in application code. Public-facing in intent (the Flutter
     * resident app will call this without auth once built), but sits behind
     * auth:sanctum for now since it's only reachable from the staff-only
     * route group at this step.
     */
    public function nearest(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $limit = (int) ($validated['limit'] ?? 10);
        $centers = EvacuationCenter::nearestTo((float) $validated['latitude'], (float) $validated['longitude'], $limit);

        return $this->success($centers);
    }

    /**
     * Replaces the full facility checklist for a center in one call --
     * matches how the EC Information Board form is actually filled out on
     * paper (the whole checklist at once), rather than one facility at a time.
     */
    public function updateFacilities(Request $request, EvacuationCenter $evacuationCenter)
    {
        $validTypes = [
            'latrine_compost_pit', 'latrine_sealed',
            'toilet_male', 'toilet_female', 'toilet_common',
            'bathing_area_male', 'bathing_area_female', 'bathing_area_common',
            'handwashing_facility', 'laundry_space',
            'women_friendly_space', 'child_friendly_space',
            'health_facility', 'prayer_room', 'community_kitchen',
            'livestock_area', 'camp_management_desk', 'info_board', 'storage_area',
        ];

        $validated = $request->validate([
            'facilities' => ['required', 'array'],
            'facilities.*.facility_type' => ['required', Rule::in($validTypes)],
            'facilities.*.quantity' => ['required', 'integer', 'min:0'],
            'facilities.*.is_available' => ['boolean'],
            'facilities.*.concerns_and_needs' => ['nullable', 'string'],
        ]);

        foreach ($validated['facilities'] as $facility) {
            EvacuationCenterFacility::updateOrCreate(
                [
                    'evacuation_center_id' => $evacuationCenter->id,
                    'facility_type' => $facility['facility_type'],
                ],
                [
                    'quantity' => $facility['quantity'],
                    'is_available' => $facility['is_available'] ?? true,
                    'concerns_and_needs' => $facility['concerns_and_needs'] ?? null,
                    'recorded_at' => now(),
                ]
            );
        }

        return $this->success(
            new EvacuationCenterResource($evacuationCenter->fresh(['barangay', 'facilities', 'creator'])),
            'Facilities updated successfully.'
        );
    }

    /**
     * The EC Information Board data for one center+event -- a separate,
     * much quicker mechanism than the detailed family/evacuee registration
     * flow, for the aggregate headcount and demographic breakdown staff
     * need first during an active evacuation. Open to any of the three
     * staff roles (not just the center's own barangay official): in an
     * emergency, whichever staff member is on hand needs to be able to
     * report or update this.
     *
     * families_now/persons_now and the age/sex breakdown are LIVE --
     * computed straight from real Evacuee/EvacuationRecord rows (see
     * EvacuationCenterQuickCount::livePersonsNow() and siblings), not a
     * manually-typed figure -- so they're always exactly what "Add
     * Evacuee" (below) and the Evacuees page's own edits/removals produce,
     * with nothing to fall out of sync. Only families_cumulative/
     * persons_cumulative/beneficiaries_4ps/sectoral_groups are still real
     * stored data; a fresh, unsaved instance stands in when no board row
     * exists yet for this center+event, so those read as zero rather than
     * 404ing -- "nothing reported yet" is the normal starting state.
     */
    public function quickCount(Request $request, EvacuationCenter $evacuationCenter)
    {
        $validated = $request->validate([
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
        ]);

        $quickCount = EvacuationCenterQuickCount::with(['updater', 'sectoralGroups'])
            ->where('evacuation_center_id', $evacuationCenter->id)
            ->where('evacuation_event_id', $validated['evacuation_event_id'])
            ->first();

        if ($quickCount) {
            $quickCount->setRelation('evacuationCenter', $evacuationCenter);
        }

        if (! $quickCount) {
            $quickCount = new EvacuationCenterQuickCount([
                'evacuation_center_id' => $evacuationCenter->id,
                'evacuation_event_id' => $validated['evacuation_event_id'],
                'families_cumulative' => 0,
                'persons_cumulative' => 0,
                'beneficiaries_4ps' => 0,
            ]);
            $quickCount->setRelation('evacuationCenter', $evacuationCenter);
        }

        return $this->success(new EvacuationCenterQuickCountResource($quickCount));
    }

    /**
     * Saves the board's remaining MANUALLY-reported figures for this
     * center+event: beneficiaries_4ps and the sectoral breakdown. Nothing
     * else is client-editable anymore -- families_cumulative/persons_cumulative
     * are server-incremented by addEvacuee() below, and families_now/
     * persons_now/the age-sex breakdown are computed live (see
     * quickCount()'s docblock). Sectoral flags (is_pwd, is_pregnant, etc.)
     * are deliberately NOT live-computed the same way age/sex is: those
     * flags are only known once someone's full details are filled in via
     * "Add details", which normally happens well after the fast headcount
     * is taken -- a live sectoral count would read as near-zero for most
     * of an active evacuation, understating real numbers rather than
     * reporting them accurately. Kept as a directly-reported aggregate
     * instead, same as it already worked before this redesign, and same
     * reasoning as beneficiaries_4ps.
     */
    public function updateQuickCount(Request $request, EvacuationCenter $evacuationCenter)
    {
        $validated = $request->validate([
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
            'beneficiaries_4ps' => ['required', 'integer', 'min:0'],
            'sectoral_groups' => ['array'],
            'sectoral_groups.*.sectoral_group' => ['required', Rule::in(EvacuationCenterQuickCount::SECTORAL_GROUPS)],
            'sectoral_groups.*.male_count' => ['required', 'integer', 'min:0'],
            'sectoral_groups.*.female_count' => ['required', 'integer', 'min:0'],
        ]);

        $quickCount = DB::transaction(function () use ($validated, $request, $evacuationCenter) {
            $quickCount = EvacuationCenterQuickCount::updateOrCreate(
                [
                    'evacuation_center_id' => $evacuationCenter->id,
                    'evacuation_event_id' => $validated['evacuation_event_id'],
                ],
                [
                    'beneficiaries_4ps' => $validated['beneficiaries_4ps'],
                    'updated_by' => $request->user()->id,
                ]
            );

            foreach ($validated['sectoral_groups'] ?? [] as $sectoralGroup) {
                $quickCount->sectoralGroups()->updateOrCreate(
                    ['sectoral_group' => $sectoralGroup['sectoral_group']],
                    ['male_count' => $sectoralGroup['male_count'], 'female_count' => $sectoralGroup['female_count']]
                );
            }

            return $quickCount;
        });

        return $this->success(
            new EvacuationCenterQuickCountResource($quickCount->fresh(['updater', 'sectoralGroups'])),
            'EC Board updated successfully.'
        );
    }

    /**
     * "Add Evacuee": the EC Board's fast entry point, replacing the old
     * typed-number-then-reconcile mechanism. Creates a REAL Evacuee (sex +
     * age_bracket_override only -- no name/birthdate yet, exactly like the
     * old placeholder, but now correctly attached to a real household
     * instead of a synthetic bulk one) plus its EvacuationRecord at this
     * center, and either attaches it to an ALREADY-registered family
     * (household_mode 'existing') or creates a brand-new one
     * (household_mode 'new' -- family_name becomes that family's display
     * name, since the evacuee being added has no name of its own to show
     * for it; see FamilyResource).
     *
     * Also records the arrival against this board's families_cumulative/
     * persons_cumulative via EvacuationCenterQuickCount::recordArrival() --
     * the one shared hook every evacuee-creating path in the app calls
     * (FamilyController::store(), EvacueeController::addMember(), and this
     * one), so cumulative reflects EVERYONE ever registered at this center
     * for this event, not just people added through this specific form.
     */
    public function addEvacuee(Request $request, EvacuationCenter $evacuationCenter)
    {
        $validated = $request->validate([
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
            'age_bracket' => ['required', Rule::in(EvacuationCenterQuickCount::AGE_BRACKETS)],
            'sex' => ['required', 'in:male,female'],
            'household_mode' => ['required', 'in:existing,new'],
            'family_id' => ['nullable', 'required_if:household_mode,existing', 'integer', 'exists:families,id'],
            'barangay_id' => ['nullable', 'required_if:household_mode,new', 'integer', 'exists:barangays,id'],
            'family_name' => ['nullable', 'required_if:household_mode,new', 'string', 'max:150'],
        ]);

        $existingFamily = null;
        if ($validated['household_mode'] === 'existing') {
            $existingFamily = Family::findOrFail($validated['family_id']);

            if ((int) $existingFamily->evacuation_event_id !== (int) $validated['evacuation_event_id']) {
                return $this->error('Selected household belongs to a different disaster event.', 422);
            }
        }

        ['family' => $family, 'evacuee_id' => $evacueeId] = DB::transaction(function () use ($validated, $request, $evacuationCenter, $existingFamily) {
            $family = $existingFamily ?? Family::create([
                'evacuation_event_id' => $validated['evacuation_event_id'],
                'barangay_id' => $validated['barangay_id'],
                'name' => $validated['family_name'],
            ]);

            $evacuee = Evacuee::create([
                'family_id' => $family->id,
                'barangay_id' => $family->barangay_id,
                'sex' => $validated['sex'],
                'age_bracket_override' => $validated['age_bracket'],
                'status' => 'active',
            ]);

            // A 4Ps beneficiary is a household-level designation -- see
            // Evacuee::propagateFourPsToFamily()'s own docblock. $family
            // is reused (not re-fetched) below, so this update is
            // reflected wherever this same instance is used afterward.
            $evacuee->setRelation('family', $family);
            $evacuee->propagateFourPsToFamily();

            EvacuationRecord::create([
                'evacuee_id' => $evacuee->id,
                'evacuation_center_id' => $evacuationCenter->id,
                'evacuation_event_id' => $validated['evacuation_event_id'],
                'displacement_type' => 'inside_center',
                'date_in' => now(),
                'status' => 'currently_evacuated',
            ]);

            EvacuationCenterQuickCount::recordArrival($evacuationCenter->id, $validated['evacuation_event_id'], $evacuee);

            SystemLog::create([
                'user_id' => $request->user()->id,
                'action' => 'evacuee.added_via_ec_board',
                'description' => sprintf(
                    '%s added a %s %s evacuee to %s via the EC Board for "%s".',
                    $request->user()->name,
                    $validated['sex'],
                    $validated['age_bracket'],
                    $existingFamily ? "family #{$family->id}" : "a new household (\"{$family->name}\")",
                    $evacuationCenter->name
                ),
                'ip_address' => $request->ip(),
            ]);

            return ['family' => $family, 'evacuee_id' => $evacuee->id];
        });

        $response = $this->success(
            new FamilyResource(
                $family->fresh()->load(['members.evacuationRecords.evacuationCenter', 'headOfFamily', 'barangay', 'evacuationEvent'])
            ),
            'Evacuee added successfully.',
            201
        );

        // The offline client needs the newly-created EVACUEE's own id (not the
        // family's, which is what `data.id` above is) to track sync status for
        // that specific evacuee -- exposed as a top-level field so the existing
        // `data` shape consumers rely on is untouched.
        $payload = $response->getData(true);
        $payload['evacuee_id'] = $evacueeId;

        return response()->json($payload, $response->getStatusCode());
    }

    /**
     * "Quick Departure": the EC Board's reverse counterpart to "Add
     * Evacuee" -- marks N currently-evacuated people at this center as
     * departed by age bracket + sex + quantity, not by picking individual
     * names, same speed-over-completeness reasoning as Add Evacuee. Open
     * to any staff role with no barangay restriction, matching Add
     * Evacuee's own access model -- this acts on whoever is physically at
     * THIS center, not on any one barangay's own roster.
     *
     * Deliberately does NOT touch families_cumulative/persons_cumulative
     * -- cumulative only ever grows on arrival (see
     * EvacuationCenterQuickCount::recordArrival()'s own docblock: "neither
     * ever decrements"); this is exactly the kind of removal that
     * invariant exists to survive. Only the live "Now" figures change,
     * the same way an individual check-out already does -- see
     * EvacuationRecord::checkOut(), which this reuses rather than
     * duplicating the date_out/status mutation.
     *
     * Selection when more than $quantity records match: oldest arrival
     * first (date_in ascending) -- simple, deterministic, and matches the
     * intuitive "whoever's been here longest leaves first" reading of a
     * departure a staff member didn't personally witness.
     */
    public function quickDeparture(Request $request, EvacuationCenter $evacuationCenter)
    {
        $validated = $request->validate([
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
            'age_bracket' => ['required', Rule::in(EvacuationCenterQuickCount::AGE_BRACKETS)],
            'sex' => ['required', 'in:male,female'],
            'quantity' => ['required', 'integer', 'min:1'],
            'status' => ['required', Rule::in(['returned_home', 'transferred'])],
        ]);

        // age_bracket is a computed accessor (real date_of_birth, or
        // age_bracket_override for a placeholder -- see
        // Evacuee::getAgeBracketAttribute()), not a real column, so it
        // can't be filtered in the query itself -- same reason
        // EvacuationCenterQuickCount::liveAgeSexBreakdown() and both
        // DROMIC report services filter in PHP after loading, not SQL.
        $candidates = EvacuationRecord::where('evacuation_center_id', $evacuationCenter->id)
            ->where('evacuation_event_id', $validated['evacuation_event_id'])
            ->where('status', 'currently_evacuated')
            ->whereNull('date_out')
            ->whereHas('evacuee', fn ($q) => $q->where('sex', $validated['sex']))
            ->with('evacuee')
            ->orderBy('date_in')
            ->get()
            ->filter(fn ($record) => $record->evacuee->age_bracket === $validated['age_bracket']);

        $available = $candidates->count();

        if ($available < $validated['quantity']) {
            return $this->error(
                "Only {$available} matching evacuee(s) are currently here, cannot mark {$validated['quantity']} as departed.",
                422
            );
        }

        $toCheckOut = $candidates->take($validated['quantity']);

        DB::transaction(function () use ($toCheckOut, $validated) {
            foreach ($toCheckOut as $record) {
                $record->checkOut($validated['status']);
            }
        });

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'evacuee.quick_departure',
            'description' => sprintf(
                '%s marked %d %s %s as %s via Quick Departure at %s.',
                $request->user()->name,
                $validated['quantity'],
                $validated['sex'],
                $validated['age_bracket'],
                $validated['status'],
                $evacuationCenter->name
            ),
            'ip_address' => $request->ip(),
        ]);

        return $this->success(null, "{$validated['quantity']} evacuee(s) marked as departed.");
    }

    /**
     * Families already registered at THIS center for THIS event -- powers
     * the "Add Evacuee -> Existing household" search/select, so staff
     * adding a second/third member of a household already here don't have
     * to recreate it. Matched via each member's own EvacuationRecord
     * (evacuation_center_id/evacuation_event_id), not the "one
     * representative center per family" heuristic FamilyResource uses
     * elsewhere -- here we want ANY family with at least one person
     * actually checked in here, not just the one center a family's FIRST
     * member happens to be at.
     */
    public function familiesAtCenter(Request $request, EvacuationCenter $evacuationCenter)
    {
        $validated = $request->validate([
            'evacuation_event_id' => ['required', 'integer', 'exists:evacuation_events,id'],
        ]);

        $families = Family::query()
            ->where('evacuation_event_id', $validated['evacuation_event_id'])
            ->whereHas('members.evacuationRecords', fn ($q) => $q
                ->where('evacuation_center_id', $evacuationCenter->id)
                ->where('evacuation_event_id', $validated['evacuation_event_id']))
            ->withCount('members')
            ->with(['headOfFamily', 'barangay'])
            ->orderBy('name')
            ->get();

        return $this->success(FamilyResource::collection($families));
    }

    /**
     * Lightweight lookup for the "Assign to barangay official" dropdown --
     * active barangay_official accounts for THIS center's own barangay
     * only, matching assignOwner()'s own validation below so the dropdown
     * never offers a choice the backend would reject anyway.
     */
    public function eligibleOwners(EvacuationCenter $evacuationCenter)
    {
        $officials = User::where('barangay_id', $evacuationCenter->barangay_id)
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('name', 'barangay_official'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return $this->success($officials);
    }

    /**
     * Hands an existing center off to a specific barangay official for
     * them to maintain going forward -- most commonly a seeder-created or
     * admin-created "[SAMPLE] ..." placeholder that a barangay official
     * can currently view but never edit, simply because they didn't
     * technically create it. Administrator/CSWD only (route-level); the
     * target user must themselves be an active barangay_official for THIS
     * center's own barangay, not just any barangay official -- checked
     * here rather than as a static validation rule, since it depends on
     * the route-bound center, not just the request body.
     */
    public function assignOwner(Request $request, EvacuationCenter $evacuationCenter)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $newOwner = User::findOrFail($validated['user_id']);

        if (! $newOwner->isBarangayOfficial()) {
            return $this->error('Ownership can only be assigned to a barangay official account.', 422);
        }

        if ($newOwner->barangay_id !== $evacuationCenter->barangay_id) {
            return $this->error("{$newOwner->name} is a barangay official for a different barangay than this center.", 422);
        }

        $evacuationCenter->update(['created_by' => $newOwner->id]);

        SystemLog::create([
            'user_id' => $request->user()->id,
            'action' => 'evacuation_center.owner_assigned',
            'description' => "{$request->user()->name} assigned evacuation center '{$evacuationCenter->name}' to {$newOwner->name} for ongoing maintenance.",
            'ip_address' => $request->ip(),
        ]);

        return $this->success(
            new EvacuationCenterResource($evacuationCenter->fresh(['barangay', 'facilities', 'creator'])),
            "Evacuation center assigned to {$newOwner->name}."
        );
    }
}
