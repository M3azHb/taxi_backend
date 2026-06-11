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
        'subtotal',
        'discount_amount',
        'amount',
        'commission_percentage',
        'commission_amount',
        'driver_earning',
        'payment_method',
        'status',
        'paid_at',
    ];

    // ─── Casts ───────────────────────────────────────────────────

    protected $casts = [
        'subtotal'              => 'decimal:2',
        'discount_amount'       => 'decimal:2',
        'amount'                => 'decimal:2',
        'commission_percentage' => 'decimal:2',
        'commission_amount'     => 'decimal:2',
        'driver_earning'        => 'decimal:2',
        'paid_at'               => 'datetime',
    ];

    // ─── Default Values ──────────────────────────────────────────

    protected $attributes = [
        'status'         => self::STATUS_PENDING,
        'payment_method' => 'cash',
    ];

    // ─── Relations ───────────────────────────────────────────────

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function discountCode(): BelongsTo
    {
        return $this->belongsTo(DiscountCode::class, 'discount_code_id');
    }

    // ─── Methods ─────────────────────────────────────────────────

    public function markAsPaid(): bool
    {
        if ($this->status === self::STATUS_PAID) {
            return false;
        }

        return $this->update([
            'status'  => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
