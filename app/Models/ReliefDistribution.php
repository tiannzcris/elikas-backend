<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReliefDistribution extends Model
{
    protected $fillable = [
        'evacuation_event_id', 'evacuation_center_id', 'relief_item_id', 'source',
        'quantity', 'unit_cost', 'distributed_by', 'distributed_at',
    ];

    // total_cost is a MySQL generated column (quantity * unit_cost); it is
    // guarded here so Eloquent never attempts to write it directly.
    protected $guarded = ['id', 'total_cost'];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'distributed_at' => 'datetime',
    ];

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }

    public function evacuationCenter(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class);
    }

    public function reliefItem(): BelongsTo
    {
        return $this->belongsTo(ReliefItem::class);
    }

    public function distributor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distributed_by');
    }
}
