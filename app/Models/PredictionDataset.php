<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionDataset extends Model
{
    protected $fillable = ['evacuation_event_id', 'parameter', 'value', 'unit', 'recorded_at'];

    protected $casts = [
        'value' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }
}
