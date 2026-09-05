<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EvacuationCenter extends Model
{
    protected $fillable = [
        'barangay_id', 'name', 'type', 'address', 'latitude', 'longitude',
        'capacity_families', 'capacity_persons', 'camp_manager_name',
        'camp_manager_contact', 'status', 'created_by', 'photo_path', 'is_seeded',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_seeded' => 'boolean',
    ];

    /**
     * photo_path stores a relative disk path (e.g.
     * "evacuation-centers/abc123.jpg"), not a full URL -- this builds the
     * actual browser-usable URL from it on demand, via the 'public' disk's
     * symlink (php artisan storage:link), so nothing here breaks if
     * APP_URL ever changes.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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

    /**
     * Haversine great-circle distance over the plain latitude/longitude
     * columns -- shared by the staff-facing and public nearest-center
     * endpoints, rather than duplicating this SQL in both controllers.
     * Replaces the old ST_Distance_Sphere(location, ...) spatial query:
     * 'location' required NOT NULL, which conflicted with a center's
     * location being genuinely optional, so it was dropped (see the
     * migration that removed it). Centers without coordinates are
     * excluded outright via the IS NOT NULL check, not sorted in with a
     * meaningless distance. LEAST/GREATEST clamp the acos() argument to
     * [-1, 1]: floating-point rounding can otherwise push it fractionally
     * out of that domain for a point very close to (or exactly at) a
     * center, which would make acos() return NULL.
     */
    public static function nearestTo(float $latitude, float $longitude, int $limit): array
    {
        return DB::select('
            SELECT id, name, type, address, latitude, longitude, capacity_persons, status,
                   (6371000 * ACOS(LEAST(1, GREATEST(-1,
                       COS(RADIANS(?)) * COS(RADIANS(latitude)) * COS(RADIANS(longitude) - RADIANS(?)) +
                       SIN(RADIANS(?)) * SIN(RADIANS(latitude))
                   )))) AS distance_meters
            FROM evacuation_centers
            WHERE status IN (\'active\', \'on_standby\')
              AND latitude IS NOT NULL AND longitude IS NOT NULL
            ORDER BY distance_meters ASC
            LIMIT ?
        ', [$latitude, $longitude, $latitude, $limit]);
    }
}
