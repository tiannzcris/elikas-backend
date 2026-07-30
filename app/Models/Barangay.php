<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Barangay extends Model
{
    protected $fillable = ['name', 'psgc_code', 'centroid_latitude', 'centroid_longitude'];

    protected $casts = [
        'centroid_latitude' => 'decimal:7',
        'centroid_longitude' => 'decimal:7',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function evacuees(): HasMany
    {
        return $this->hasMany(Evacuee::class);
    }

    public function evacuationCenters(): HasMany
    {
        return $this->hasMany(EvacuationCenter::class);
    }

    public function hazardProneAreas(): HasMany
    {
        return $this->hasMany(HazardProneArea::class);
    }

    public function damagedHouses(): HasMany
    {
        return $this->hasMany(DamagedHouse::class);
    }

    public function cashAssistanceDisbursements(): HasMany
    {
        return $this->hasMany(CashAssistanceDisbursement::class);
    }
}
