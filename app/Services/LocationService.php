<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverLocation;

class LocationService
{
    public function updateDriverLocation(Driver $driver, array $data)
    {
        return DriverLocation::updateOrCreate(
            ['driver_id' => $driver->id],
            [
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'heading' => $data['heading'] ?? null,
                'speed' => $data['speed'] ?? null,
                'recorded_at' => now(),
            ]
        );
    }
}