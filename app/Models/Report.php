<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'evacuation_event_id', 'evacuation_center_id', 'report_type', 'file_format', 'file_path', 'generated_by', 'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function evacuationEvent(): BelongsTo
    {
        return $this->belongsTo(EvacuationEvent::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /** The center an EC Information Board export is for; null for city-wide reports. */
    public function evacuationCenter(): BelongsTo
    {
        return $this->belongsTo(EvacuationCenter::class);
    }

    /**
     * Whether $user may list/download this report: administrators and CSWD
     * personnel, any report; a barangay official, only an EC Information
     * Board export for a center in their own barangay -- the same limits
     * as generating them (city-wide DROMIC Region V is admin/CSWD only).
     */
    public function isVisibleTo(User $user): bool
    {
        if (! $user->isBarangayOfficial()) {
            return true;
        }

        return $this->report_type === 'ec_information_board'
            && $this->evacuationCenter
            && (int) $this->evacuationCenter->barangay_id === (int) $user->barangay_id;
    }
}
