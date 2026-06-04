<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Driver;

class DriverLocation extends Model
{
    protected $fillable = [
        'driver_id',
        'latitude',
        'longitude',
        'heading',
        'speed',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'heading' => 'decimal:2',
        'speed' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function distanceFrom(float $lat, float $lng)
    {
        $earthRadius = 6371;

        $latFrom = deg2rad($lat);
        $lngFrom = deg2rad($lng);
        $latTo = deg2rad($this->latitude);
        $lngTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2 +
            cos($latFrom) * cos($latTo) *
            sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}