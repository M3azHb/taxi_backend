<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountCode extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'discount_percentage',
        'usage_limit',
        'used_count',
        'is_active',
        'expiry_date',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'usage_limit'         => 'integer',
        'used_count'          => 'integer',
        'is_active'           => 'boolean',
        'expiry_date'         => 'datetime',
    ];

    // Relations

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Methods

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expiry_date && now()->isAfter($this->expiry_date->endOfDay())) {
            return false;
        }

        if (! is_null($this->usage_limit) && $this->usage_limit > 0
            && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        return round($amount * ((float) $this->discount_percentage / 100), 2);
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }


    public function applyDiscount(float $amount): float
    {
        return round($amount - $this->calculateDiscount($amount), 2);
    }
}
