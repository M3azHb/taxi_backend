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

    const STATUS_PENDING        = 'pending';        // انتظار قبول السائق
    const STATUS_ACCEPTED       = 'accepted';       // قبل السائق
    const STATUS_DRIVER_ARRIVED = 'driver_arrived'; // وصل السائق لنقطة الالتقاء
    const STATUS_IN_PROGRESS    = 'in_progress';    // الرحلة جارية
    const STATUS_COMPLETED      = 'completed';      // اكتملت
    const STATUS_CANCELLED      = 'cancelled';      // ألغيت
    const STATUS_REJECTED       = 'rejected';       // رفض السائق

    // ─── Fillable ────────────────────────────────────────────────

    protected $fillable = [
        'customer_id',
        'driver_id',
        'car_id',
        'car_type_id',
        'discount_code_id',

        // نقطة الانطلاق
        'pickup_latitude',
        'pickup_longitude',
        'address_pickup',

        // الوجهة
        'destination_latitude',
        'destination_longitude',
        'address_destination',

        // التسعير
        'estimated_fare',
        'final_fare',
        'distance_km',
        'duration_minutes',
        'discount_amount',

        // الحالة
        'status',

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
        // الإحداثيات بدقة 7 منازل عشرية
        'pickup_latitude'       => 'decimal:7',
        'pickup_longitude'      => 'decimal:7',
        'destination_latitude'  => 'decimal:7',
        'destination_longitude' => 'decimal:7',

        // المبالغ المالية بمنزلتين عشريتين
        'estimated_fare'        => 'decimal:2',
        'final_fare'            => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'distance_km'           => 'decimal:2',

        // duration عدد صحيح (دقائق)
        'duration_minutes'      => 'integer',

        // الـ Timestamps الستة — datetime للحفاظ على الوقت الدقيق
        'requested_at'          => 'datetime',
        'accepted_at'           => 'datetime',
        'driver_arrived_at'     => 'datetime',
        'started_at'            => 'datetime',
        'completed_at'          => 'datetime',
        'cancelled_at'          => 'datetime',
    ];

    // ─── Default Values ──────────────────────────────────────────

    // كل رحلة تبدأ بـ pending — تحمي من NULL في DB
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

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    // HasMany — رحلة واحدة لها نقاط تتبع متعددة
    public function trackings(): HasMany
    {
        return $this->hasMany(Tracking::class);
    }

    // HasOne — رحلة واحدة لها دفعة واحدة فقط
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    // HasOne — رحلة واحدة لها تقييم واحد فقط
    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    // HasMany — رحلة واحدة قد تحتوي بلاغات متعددة
    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────

    /**
     * الرحالت النشطة: كل ما هو قبل الإكمال أو الإلغاء.
     */
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

    /**
     * هل الرحلة نشطة؟
     * pending / accepted / driver_arrived فقط — in_progress مستثنى عمداً
     * لأن المنطق يعتبر "نشطة" = قبل بدء السير الفعلي
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_ACCEPTED,
            self::STATUS_DRIVER_ARRIVED,
        ]);
    }

    /**
     * هل يمكن إلغاء الرحلة؟
     * CRITICAL: in_progress = false — لا يمكن إلغاء رحلة بدأت فعلاً
     * completed / cancelled / rejected = false كذلك
     */
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

    /**
     * هل تم تقييم هذه الرحلة؟
     * يستخدم relationLoaded لتجنب query زائدة عند eager loading.
     */
    public function isRated(): bool
    {
        if ($this->relationLoaded('rating')) {
            return ! is_null($this->rating);
        }

        return $this->rating()->exists();
    }

    /**
     * المدة الفعلية للرحلة بالدقائق.
     * يُرجع null إذا لم تكتمل الرحلة بعد.
     */
    public function getActualDurationMinutes(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes($this->completed_at);
    }
}
