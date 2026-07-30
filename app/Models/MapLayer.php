<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapLayer extends Model
{
    protected $fillable = ['hazard_area_id', 'layer_name', 'layer_type', 'geojson_data'];

    protected $casts = [
        'geojson_data' => 'array',
    ];

    public function hazardArea(): BelongsTo
    {
        return $this->belongsTo(HazardProneArea::class, 'hazard_area_id');
    }
}
