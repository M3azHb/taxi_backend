<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    //  Constants

    const STATUS_PENDING   = 'pending';
    const STATUS_REVIEWED  = 'reviewed';
    const STATUS_RESOLVED  = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    // Fillable

    protected $fillable = [
        'ride_id',
        'reporter_id',
        'reporter_type',
        'reported_id',
        'reported_type',
        'description',
        'status',
    ];

    //  Casts

        protected $casts = [
        'status' => 'string',
    ];

    // BUG FIX #7: لم يكن هناك default للـ status عند الإنشاء
    // إذا أنشأ Controller تقريراً بدون تحديد status ستكون NULL في DB
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    //  Relations

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    /**
     * السيارة المشكو منها في هذه الرحلة.
     */
    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    /**
     * من قدّم البلاغ (Customer أو Driver).
     * اسم العلاقة يطابق بادئة الأعمدة: reporter_id / reporter_type
     */
    public function reporter(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * من تم الإبلاغ عنه (Customer أو Driver).
     */
    public function reported(): MorphTo
    {
        return $this->morphTo();
    }

    //  Scope

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeReviewed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REVIEWED);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    // Methods

    /**
     * غيّر حالة البلاغ — يمنع الانتقال لحالة غير صالحة.
     */
    public function updateStatus(string $status): bool
    {
        $allowed = [
            self::STATUS_PENDING,
            self::STATUS_REVIEWED,
            self::STATUS_RESOLVED,
            self::STATUS_DISMISSED,
        ];

        if (! in_array($status, $allowed)) {
            return false;
        }

        return $this->update(['status' => $status]);
    }
}
