<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ride extends Model
{
    // ─── Status Constants — الحالات السبع ────────────────────────

    const STATUS_PENDING        = 'pending';
    const STATUS_ACCEPTED       = 'accepted';
    const STATUS_DRIVER_ARRIVED = 'driver_arrived';
    const STATUS_IN_PROGRESS    = 'in_progress';
    const STATUS_COMPLETED      = 'completed';
    const STATUS_CANCELLED      = 'cancelled';
    const STATUS_REJECTED       = 'rejected';

    // ─── Fillable ────────────────────────────────────────────────

    protected $fillable = [
        'customer_id',
        'driver_id',
        'car_id',
        'car_type_id',

        // نقطة الانطلاق (التصحيح بناءً على الميجريشن)
        'pickup_latitude',
        'pickup_longitude',
        'pickup_address',

        // الوجهة (التصحيح بناءً على الميجريشن)
        'destination_latitude',
        'destination_longitude',
        'destination_address',

        // التسعير
        'estimated_fare',
        'final_fare',
        'distance_km',
        'duration_minutes',

        // الحالة
        'status',
        'cancelled_by',
        'cancellation_reason',

        // الـ Timestamps الستة
        'requested_at',
        'accepted_at',
        'driver_arrived_at',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    // ─── Casts ───────────────────────────────────────────────────

    protected $casts = [
        'pickup_latitude'       => 'decimal:7',
        'pickup_longitude'      => 'decimal:7',
        'destination_latitude'  => 'decimal:7',
        'destination_longitude' => 'decimal:7',

        'estimated_fare'        => 'decimal:2',
        'final_fare'            => 'decimal:2',
        'distance_km'           => 'decimal:2',

        'duration_minutes'      => 'integer',

        'requested_at'          => 'datetime',
        'accepted_at'           => 'datetime',
        'driver_arrived_at'     => 'datetime',
        'started_at'            => 'datetime',
        'completed_at'          => 'datetime',
        'cancelled_at'          => 'datetime',
    ];

    // ─── Default Values ──────────────────────────────────────────

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    // ─── Relations ───────────────────────────────────────────────

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(Car::class);
    }

    public function carType(): BelongsTo
    {
        return $this->belongsTo(CarType::class);
    }

    public function trackings(): HasMany
    {
        return $this->hasMany(Tracking::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    // ─── Helper Methods ──────────────────────────────────────────

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
            self::STATUS_IN_PROGRESS,
        ]);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * يمكن تقييم الرحلة فقط إذا اكتملت ولم تُقيَّم بعد.
     */
    public function canBeRated(): bool
    {
        return $this->isCompleted() && ! $this->isRated();
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isTerminated(): bool
    {
        return in_array($this->status, [
            self::STATUS_CANCELLED,
            self::STATUS_REJECTED,
        ]);
    }

    public function isRated(): bool
    {
        if ($this->relationLoaded('rating')) {
            return ! is_null($this->rating);
        }

        return $this->rating()->exists();
    }

    public function getActualDurationMinutes(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes($this->completed_at);
    }
}
