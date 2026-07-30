<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EvacuationCenter\StoreEvacuationCenterRequest;
use App\Http\Requests\EvacuationCenter\UpdateEvacuationCenterRequest;
use App\Http\Resources\EvacuationCenterResource;
use App\Models\EvacuationCenter;
use App\Models\EvacuationCenterFacility;
use App\Models\SystemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            new EvacuationCenterResource($evacuationCenter->load(['barangay', 'facilities']))
        );
    }

    public function store(StoreEvacuationCenterRequest $request)
    {
        $validated = $request->validated();

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
            new EvacuationCenterResource($center->fresh(['barangay', 'facilities'])),
            'Evacuation center created successfully.',
            201
        );
    }

    public function update(UpdateEvacuationCenterRequest $request, EvacuationCenter $evacuationCenter)
    {
        $validated = $request->validated();

        $evacuationCenter->update($validated);

        return $this->success(
            new EvacuationCenterResource($evacuationCenter->fresh(['barangay', 'facilities'])),
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
        $point = "POINT({$validated['longitude']} {$validated['latitude']})";

        $centers = DB::select("
            SELECT id, name, type, address, latitude, longitude, capacity_persons, status,
                   ST_Distance_Sphere(location, ST_GeomFromText(?)) AS distance_meters
            FROM evacuation_centers
            WHERE status IN ('active', 'on_standby')
            ORDER BY distance_meters ASC
            LIMIT {$limit}
        ", [$point]);

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
            new EvacuationCenterResource($evacuationCenter->fresh(['barangay', 'facilities'])),
            'Facilities updated successfully.'
        );
    }
}
