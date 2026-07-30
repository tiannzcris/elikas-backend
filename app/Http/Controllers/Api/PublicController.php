<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlertResource;
use App\Models\Alert;
use App\Models\EvacuationCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Every endpoint here is genuinely public -- no Sanctum token, no login,
 * matching the scope in Chapter 1: residents use the Flutter app without
 * an account. This is a deliberately separate controller (rather than
 * reusing the staff-facing controllers' methods directly) so it's obvious
 * at a glance, for anyone auditing routes/api.php, exactly which data this
 * system exposes without authentication -- and so each method can
 * hand-pick only the fields appropriate for public viewing (e.g. no camp
 * manager contact numbers, no draft/unsent alerts).
 */
class PublicController extends Controller
{
    /**
     * Evacuation centers for the resident map/list. Deliberately excludes
     * camp_manager_name/camp_manager_contact -- those are staff contact
     * details, not meant for public viewing, unlike the same data returned
     * to authenticated staff via EvacuationCenterResource.
     */
    public function evacuationCenters()
    {
        $centers = EvacuationCenter::with('barangay')->get()->map(fn (EvacuationCenter $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type,
            'address' => $c->address,
            'barangay' => $c->barangay?->name,
            'latitude' => (float) $c->latitude,
            'longitude' => (float) $c->longitude,
            'capacity_persons' => $c->capacity_persons,
            'current_occupancy' => $c->currentOccupancy(),
            'occupancy_percent' => $c->occupancyPercent(),
            'status' => $c->status,
        ]);

        return $this->success($centers);
    }

    /**
     * Same nearest-center search staff use internally, opened up publicly
     * -- this is the actual "which evacuation center should I go to" query
     * a resident's app needs, using the spatial index for an efficient
     * distance search rather than pulling every row into the app.
     */
    public function nearestEvacuationCenters(Request $request)
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
     * Only alerts that were actually SENT -- drafts should never be
     * visible to residents, which is why this can't just reuse
     * AlertController::index() as-is (that one shows every status to staff).
     */
    public function alerts(Request $request)
    {
        $alerts = Alert::query()
            ->where('status', 'sent')
            ->with('evacuationEvent', 'sender')
            ->latest('date_sent')
            ->paginate($request->integer('per_page', 20));

        return $this->success(AlertResource::collection($alerts)->response()->getData(true));
    }
}
