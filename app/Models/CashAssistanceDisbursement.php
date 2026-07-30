<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashAssistanceDisbursement extends Model
{
    protected $fillable = [
        'evacuation_event_id', 'barangay_id', 'program',
        'number_of_beneficiaries', 'unit_cost', 'disbursed_at',
    ];

    protected $guarded = ['id', 'total_cost']; // generated column

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'disbursed_at' => 'datetime',
    ];

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }
}
