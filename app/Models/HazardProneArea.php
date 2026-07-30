<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class HazardProneArea extends Model
{
    // 'geometry' (spatial POLYGON) is intentionally excluded from $fillable
    // -- Eloquent has no native spatial cast, and the column is set via the
    // saving() hook below instead. This is the same fragility-fix applied
    // to EvacuationCenter::location: a NOT NULL spatial column populated
    // only by a separate follow-up UPDATE would fail its very first INSERT
    // whenever created any way other than through the one controller method
    // that remembered to also run that follow-up statement (MySQL/MariaDB
    // both require spatial-indexed columns to be NOT NULL with no
    // exception, so making the column nullable isn't an option either).
    protected $fillable = ['barangay_id', 'area_name', 'hazard_type', 'description'];

    /**
     * Not a database column -- a transient property the controller sets
     * (as WKT, e.g. "POLYGON((...))") before calling create()/save().
     * Converted into the actual geometry column value by the hook below,
     * as part of the same INSERT/UPDATE statement.
     */
    public ?string $pendingGeometryWkt = null;

    protected static function booted(): void
    {
        static::saving(function (HazardProneArea $area) {
            if ($area->pendingGeometryWkt !== null) {
                $area->geometry = DB::raw("ST_GeomFromText('{$area->pendingGeometryWkt}')");
            }
        });
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function mapLayers(): HasMany
    {
        return $this->hasMany(MapLayer::class, 'hazard_area_id');
    }
}
