<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'ride_id',
        'reporter_id', 'reporter_type',
        'reported_id', 'reported_type',
        'reason', 'status'
    ];

    // الثوابت المطلوبة في ملفك (شهد.pdf)


    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_RESOLVED = 'resolved';

    // علاقة البلاغ بالرحلة (سيدرا اللي عاملة جدول الـ rides)
    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    // علاقات الـ Polymorphic عشان السائق والزبون (شغل معاذ)
    public function reporter(): MorphTo
    {
        return $this->morphTo();
    }

    public function reported(): MorphTo
    {
        return $this->morphTo();
    }
}
