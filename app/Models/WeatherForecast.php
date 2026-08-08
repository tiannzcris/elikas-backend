<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeatherForecast extends Model
{
    protected $fillable = [
        'metric', 'horizon', 'forecast_points', 'diagnostics',
        'is_sample_based', 'generated_by', 'generated_at',
    ];

    protected $casts = [
        'forecast_points' => 'array',
        'diagnostics' => 'array',
        'is_sample_based' => 'boolean',
        'generated_at' => 'datetime',
    ];

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
