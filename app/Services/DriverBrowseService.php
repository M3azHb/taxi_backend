<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Customer;
use App\Models\BlockList;
use Illuminate\Support\Collection;

class DriverBrowseService
{
    /**
     * جلب السائقين المتاحين وتصفيتهم وحساب المسافة
     */
    public function getAvailableDrivers(Customer $customer, array $params): Collection
    {
        // 1. جلب السائقين المتاحين مع علاقاتهم
        $drivers = Driver::online()
            ->with(['car.carType', 'location'])
            ->when($params['car_type_id'] ?? null, function ($q, $typeId) {
                $q->whereHas('car', fn($q) => $q->where('car_type_id', $typeId));
            })
            ->get();

        // 2. استبعاد من حظره الزبون أو حظروه (الحظر المتبادل)
        $drivers = $drivers->filter(function ($driver) use ($customer) {
            return !BlockList::isBlockedEither($customer, $driver);
        });

        // 3. حساب المسافة لكل سائق إن وجد موقعه
        $drivers->each(function ($driver) use ($params) {
            if ($driver->location) {
                $driver->distance_km = $driver->location->distanceFrom($params['latitude'], $params['longitude']);
            }
        });

        // 4. التصفية حسب الـ Radius (الافتراضي 5 كم)
        $radius = $params['radius_km'] ?? 5;
        $drivers = $drivers->filter(fn($d) => ($d->distance_km ?? 0) <= $radius);

        // 5. الترتيب حسب المسافة الأقرب
        return $drivers->sortBy('distance_km')->values();
    }

    /**
     * جلب تفاصيل سائق معين
     */
    public function getDriverDetails(int $driverId): Driver
    {
        return Driver::with(['car.carType'])
            ->where('is_active', true)
            ->findOrFail($driverId);
    }
}
