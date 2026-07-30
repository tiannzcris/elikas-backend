<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvacuationEvent extends Model
{
    protected $fillable = [
        'name', 'event_type', 'typhoon_category', 'max_wind_speed_kph',
        'rainfall_mm', 'alert_level', 'start_date', 'end_date', 'status', 'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'max_wind_speed_kph' => 'decimal:2',
        'rainfall_mm' => 'decimal:2',
    ];

    public function families(): HasMany
    {
        return $this->hasMany(Family::class);
    }

    public function evacuationRecords(): HasMany
    {
        return $this->hasMany(EvacuationRecord::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function reliefDistributions(): HasMany
    {
        return $this->hasMany(ReliefDistribution::class);
    }

    public function cashAssistanceDisbursements(): HasMany
    {
        return $this->hasMany(CashAssistanceDisbursement::class);
    }

    public function damagedHouses(): HasMany
    {
        return $this->hasMany(DamagedHouse::class);
    }

    public function locallyStrandedIndividuals(): HasMany
    {
        return $this->hasMany(LocallyStrandedIndividual::class);
    }

    public function predictionDatasets(): HasMany
    {
        return $this->hasMany(PredictionDataset::class);
    }

    public function analyticsPredictions(): HasMany
    {
        return $this->hasMany(AnalyticsPrediction::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    // Aggregate helpers used by the dashboard KPI cards and DROMIC report builder
    public function totalFamiliesDisplaced(): int
    {
        return $this->families()->count();
    }

    public function totalPersonsDisplaced(): int
    {
        return EvacuationRecord::where('evacuation_event_id', $this->id)
            ->where('status', 'currently_evacuated')
            ->count();
    }
}
