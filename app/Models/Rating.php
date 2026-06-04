<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    // ─── Fillable ────────────────────────────────────────────────

    protected $fillable = [
        'ride_id',
        'customer_id',
        'driver_id',
        'score',
        'comment',
    ];

    // ─── Casts ───────────────────────────────────────────────────

    protected $casts = [
        'score' => 'integer',
    ];

    // ─── Relations ───────────────────────────────────────────────

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    // ─── Eloquent Events ─────────────────────────────────────────

    /**
     * عند إنشاء تقييم جديد — حدّث متوسط السائق تلقائياً.
     *
     * الصيغة الصحيحة للمتوسط المتحرك:
     * newAverage = (oldAverage × oldCount + newScore) / (oldCount + 1)
     *
     * CRITICAL: لا نستخدم AVG() من DB لأنها تتجاهل الـ count المحفوظ
     * ونستخدم الصيغة الحسابية للحفاظ على الدقة مع الـ count الفعلي.
     *
     * لماذا booted() وليس boot()؟
     * booted() أحدث وأنظف — تُستدعى بعد تهيئة الـ Model الكاملة.
     * boot() تعمل أيضاً لكن booted() أفضل ممارسة في Laravel 8+.
     */
    protected static function booted(): void
    {
        static::created(function (Rating $rating) {
            $driver = $rating->driver;

            if (! $driver) {
                return; // لا نكسر النظام إذا لم يُوجد السائق
            }

            $oldAverage = (float) $driver->average_rating;
            $oldCount   = (int)   $driver->count_rating;
            $newScore   = (int)   $rating->score;

            // الصيغة الصحيحة للمتوسط المتحرك
            $newAverage = ($oldAverage * $oldCount + $newScore) / ($oldCount + 1);
            $newCount   = $oldCount + 1;

            // round لمنزلتين عشريتين — قيم مثل 4.666666 تُخزن كـ 4.67
            $driver->update([
                'average_rating' => round($newAverage, 2),
                'count_rating'   => $newCount,
            ]);
        });
    }
}
