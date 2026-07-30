<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
