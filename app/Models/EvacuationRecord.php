<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class EvacuationRecord extends Model
{
    protected $fillable = [
        'evacuee_id', 'evacuation_center_id', 'evacuation_event_id',
        'displacement_type', 'date_in', 'date_out', 'status', 'notes',
    ];

    protected $casts = [
        'date_in' => 'datetime',
        'date_out' => 'datetime',
    ];

    public function evacuee(): BelongsTo
    {
        return $this->belongsTo(Evacuee::class);
    }

    public function evacuationCenter(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class);
    }

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }

    /**
     * Closes out this active record: sets date_out/status and mirrors the
     * same status onto the evacuee. The one shared mutation both
     * EvacueeController::checkOut() (single evacuee, by name) and
     * EvacuationCenterController::quickDeparture() (bulk, by age
     * bracket + sex + quantity) call, so what actually happens on
     * departure only ever exists in one place. Deliberately does NOT
     * touch EvacuationCenterQuickCount's cumulative figures -- those only
     * ever grow on arrival (see EvacuationCenterQuickCount::recordArrival()'s
     * own docblock: "neither ever decrements"); this is exactly the kind
     * of removal that invariant exists to survive. Only the live "Now"
     * figures change, since those are computed straight from this
     * record's status/date_out every time the board loads.
     */
    public function checkOut(string $status): void
    {
        // Both writes or neither -- a record closed with its evacuee still
        // 'active' (or the reverse) is exactly the mismatch this prevents.
        // Nests safely inside quickDeparture()'s own transaction.
        DB::transaction(function () use ($status) {
            $this->update(['date_out' => now(), 'status' => $status]);
            $this->evacuee->update(['status' => $status]);
        });
    }
}
