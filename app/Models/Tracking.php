<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tracking extends Model
{
    protected $fillable = [
        'ride_id',
        'latitude',
        'longitude',
        // CRITICAL: اسم الحقل هو created_at وليس recorded_at حسب التوثيق
        // لكن نُبقي recorded_at في fillable إذا أضافه فريق DB
    ];

    protected $casts = [
        'latitude'  => 'decimal:7',
        'longitude' => 'decimal:7',
        // created_at يُعالَج تلقائياً من Eloquent كـ datetime
    ];

    // ─── Relation ────────────────────────────────────────────────

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }
}
