<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    // ─── Constants ───────────────────────────────────────────────

    const STATUS_PENDING = 'pending';
    const STATUS_PAID    = 'paid';

    // ─── Fillable ────────────────────────────────────────────────

    protected $fillable = [
        'ride_id',
        'discount_code_id',
        'amount',
        'discount_amount',
        'final_amount',
        'status',
        'paid_at',
    ];

    // ─── Casts ───────────────────────────────────────────────────

    protected $casts = [
        'amount'          => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'final_amount'    => 'decimal:2',
        'paid_at'         => 'datetime',
    ];

    // ─── Default Values ──────────────────────────────────────────

    // كل payment يبدأ بـ pending — تحمي من NULL في DB
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    // ─── Relations ───────────────────────────────────────────────

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class);
    }

    // ─── Methods ─────────────────────────────────────────────────

    /**
     * سجّل استلام الدفع.
     *
     * CRITICAL: يجب التحقق من الحالة قبل التحديث
     * لا نُعيد كتابة paid_at إذا كان مدفوعاً مسبقاً
     * (نفس مشكلة markAsRead في Notification)
     */
    public function markAsPaid(): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return false; // مدفوع مسبقاً — لا نفعل شيئاً
        }

        return $this->update([
            'status'  => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * هل تم الدفع؟
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * هل الدفع معلق؟
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
