<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeatherReading extends Model
{
    protected $fillable = [
        'reading_date', 'station', 'rainfall_mm', 'wind_speed_kph', 'is_sample', 'source_file',
    ];

    protected $casts = [
        // Explicit Y-m-d format, not the plain 'date' cast -- a bare 'date'
        // cast reads back as Y-m-d but SERIALIZES writes using the
        // connection's full Y-m-d H:i:s datetime format, so a raw
        // ->toDateString() comparison in a WHERE clause (as
        // ImportWeatherReadings does for its upsert/protection lookups)
        // would never match the stored value. Confirmed via a real test
        // run: without this, updateOrCreate() never finds the previously-
        // imported row and throws a unique-constraint violation trying to
        // insert a duplicate on every re-import.
        'reading_date' => 'date:Y-m-d',
        'rainfall_mm' => 'decimal:2',
        'wind_speed_kph' => 'decimal:2',
        'is_sample' => 'boolean',
    ];
}
