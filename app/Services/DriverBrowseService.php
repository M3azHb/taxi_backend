<?php

namespace App\Services;

use App\Models\BlockList;
use App\Models\Customer;
use App\Models\Driver;
use Illuminate\Support\Collection;

class DriverBrowseService
{
    /**
     * Get all available (online) drivers near the customer, filtered by car type,
     * blocking relationship and radius, sorted by distance.
     */
    public function getAvailableDrivers(Customer $customer, array $params): Collection
    {
        // 1. Online drivers + relations
        $drivers = Driver::online()
            ->with(['car.carType', 'location'])
            ->when($params['car_type_id'] ?? null, function ($q, $typeId) {
                $q->whereHas('car', fn ($q2) => $q2->where('car_type_id', $typeId));
            })
            ->get();

        // 2. Exclude blocked relationships (in either direction)
        $drivers = $drivers->filter(function (Driver $driver) use ($customer) {
            return !BlockList::isBlockedEither($customer, $driver);
        });

        // 3. Calculate distance for each driver
        $drivers->each(function (Driver $driver) use ($params) {
            if ($driver->location) {
                $driver->distance_km = $driver->location->distanceFrom(
                    $params['latitude'],
                    $params['longitude']
                );
            } else {
                $driver->distance_km = null;
            }
        });

        // 4. Filter by radius if provided
        $radius = $params['radius_km'] ?? 5;
        $drivers = $drivers->filter(function (Driver $driver) use ($radius) {
            return $driver->distance_km !== null && $driver->distance_km <= $radius;
        });

        // 5. Sort by distance
        $drivers = $drivers->sortBy('distance_km')->values();

        // 6. Return
        return $drivers;
    }

    /**
     * Get full details of a single driver.
     */
    public function getDriverDetails(int $driverId): Driver
    {
        return Driver::with(['car.carType'])
            ->where('is_active', true)
            ->findOrFail($driverId);
    }
}
