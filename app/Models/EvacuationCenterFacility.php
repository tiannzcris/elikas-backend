<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvacuationCenterFacility extends Model
{
    protected $fillable = [
        'evacuation_center_id', 'facility_type', 'quantity', 'is_available',
        'concerns_and_needs', 'recorded_at',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function evacuationCenter(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class);
    }
}
