<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Family extends Model
{
    protected $fillable = [
        'evacuation_event_id', 'barangay_id', 'head_of_family_evacuee_id', 'is_4ps_beneficiary',
    ];

    protected $casts = [
        'is_4ps_beneficiary' => 'boolean',
    ];

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function headOfFamily(): BelongsTo
    {
        return $this->belongsTo(Evacuee::class, 'head_of_family_evacuee_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Evacuee::class);
    }

    public function memberCount(): int
    {
        return $this->members()->count();
    }
}
