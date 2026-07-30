<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocallyStrandedIndividual extends Model
{
    protected $table = 'locally_stranded_individuals';

    protected $fillable = [
        'evacuation_event_id', 'origin_location', 'families_count', 'persons_count', 'recorded_at',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
    ];

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }
}
