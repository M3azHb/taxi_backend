<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rating extends Model
{
    // Fillable

    protected $fillable = [
        'ride_id',
        'customer_id',
        'driver_id',
        'score',
        'comment',
    ];

    // Casts

    protected $casts = [
        'score' => 'integer',
    ];

    // Relations

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

    // Eloquent Events

    protected static function booted(): void
    {
        static::created(function (Rating $rating) {
            $driver = $rating->driver;

            if (!$driver) {
                return;
            }

            $oldAverage = (float) $driver->rating_average;
            $oldCount   = (int)   $driver->rating_count;
            $newScore   = (int)   $rating->score;

            $newCount   = $oldCount + 1;
            $newAverage = (($oldAverage * $oldCount) + $newScore) / $newCount;

            $driver->update([
                'rating_average' => round($newAverage, 2),
                'rating_count'   => $newCount,
            ]);
        });
    }
}
