<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class EvacuationCenter extends Model
{
    protected $fillable = [
        'barangay_id', 'name', 'type', 'address', 'latitude', 'longitude',
        'capacity_families', 'capacity_persons', 'camp_manager_name',
        'camp_manager_contact', 'status',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Automatically keeps the spatial 'location' column in sync with the
     * plain latitude/longitude columns, as part of the SAME insert/update
     * statement Eloquent is already about to run -- not a separate follow-up
     * step that has to be remembered and called manually. That previous
     * design broke the moment a EvacuationCenter got created any way other
     * than through the one controller method that remembered to call it
     * (e.g. directly via `EvacuationCenter::create()` in tinker), because
     * 'location' is NOT NULL with no default (required for the spatial
     * index -- MySQL/MariaDB do not allow spatial indexes on nullable
     * columns, confirmed across every current version), so the INSERT
     * itself failed before any follow-up UPDATE could run.
     *
     * Setting the attribute to a DB::raw() expression here causes Eloquent
     * to embed that raw SQL directly into the INSERT/UPDATE it was already
     * building, rather than needing a second statement at all.
     */
    protected static function booted(): void
    {
        static::saving(function (EvacuationCenter $center) {
            if ($center->latitude !== null && $center->longitude !== null) {
                $center->location = DB::raw("ST_GeomFromText('POINT({$center->longitude} {$center->latitude})')");
            }
        });
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(EvacuationCenterFacility::class);
    }

    public function evacuationRecords(): HasMany
    {
        return $this->hasMany(EvacuationRecord::class);
    }

    public function reliefDistributions(): HasMany
    {
        return $this->hasMany(ReliefDistribution::class);
    }

    public function currentOccupancy(): int
    {
        return $this->evacuationRecords()
            ->where('displacement_type', 'inside_center')
            ->where('status', 'currently_evacuated')
            ->count();
    }

    public function occupancyPercent(): ?float
    {
        if (! $this->capacity_persons) {
            return null;
        }

        return round(($this->currentOccupancy() / $this->capacity_persons) * 100, 1);
    }
}
