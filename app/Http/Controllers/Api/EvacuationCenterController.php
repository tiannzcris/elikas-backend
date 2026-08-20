<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvacuationCenter\StoreEvacuationCenterRequest;
use App\Http\Requests\EvacuationCenter\UpdateEvacuationCenterRequest;
use App\Http\Resources\EvacuationCenterResource;
use App\Models\EvacuationCenter;
use App\Models\EvacuationCenterFacility;
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
