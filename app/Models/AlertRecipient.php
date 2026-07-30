<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRecipient extends Model
{
    protected $fillable = ['alert_id', 'recipient_type', 'recipient_value', 'status', 'date_sent'];

    protected $casts = [
        'date_sent' => 'datetime',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }
}
