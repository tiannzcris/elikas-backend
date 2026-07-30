<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsPrediction extends Model
{
    protected $fillable = [
        'evacuation_event_id', 'predicted_evacuees', 'predicted_center_occupancy',
        'predicted_resources_needed', 'model_used', 'mae_score', 'r2_score',
        'input_payload', 'generated_at',
    ];

    protected $casts = [
        'predicted_resources_needed' => 'decimal:2',
        'mae_score' => 'decimal:4',
        'r2_score' => 'decimal:4',
        'input_payload' => 'array',
        'generated_at' => 'datetime',
    ];

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }
}
