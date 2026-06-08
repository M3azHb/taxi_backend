<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tracking extends Model
{
    protected $table = 'trackings';

    protected $fillable = [
        'ride_id',
        'latitude',
        'longitude',
        'recorded_at', // تم تثبيتها بناءً على الميجريشن الفعلي
    ];

    protected $casts = [
        'latitude'    => 'decimal:7',
        'longitude'   => 'decimal:7',
        'recorded_at' => 'datetime',
    ];

    // ─── Relation ────────────────────────────────────────────────

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }
}
