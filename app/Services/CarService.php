<?php

namespace App\Services;

use App\Models\CarType;
use App\Models\Driver;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Car;

class CarService
{
    public function getActiveCarTypes(): Collection
    {
        return CarType::where('is_active', true)->get();
    }

    public function getDriverCar(Driver $driver)
{
    return $driver->cars()
        ->with('carType')
        ->first();
}
   
   public function addCarForDriver(Driver $driver, array $data): Car
{
    if ($driver->cars()->exists()) {
        throw new \Exception('لديك سيارة مسجلة، يمكنك تحديثها فقط');
    }

    $data['driver_id'] = $driver->id;

    $car = Car::create($data);

    return $car->load('carType');
}

public function updateDriverCar(Driver $driver, array $data): Car
{
    $car = $driver->cars()->firstOrFail();

    $car->update($data);

    return $car->refresh()->load('carType');
}

public function updateAvailability(Driver $driver, string $availability): bool
{
    if (
        $availability === Driver::AVAILABILITY_OFFLINE &&
        $driver->availability === Driver::AVAILABILITY_BUSY
    ) {
        throw new \Exception('لا يمكن تغيير الحالة أثناء رحلة نشطة');
    }

    $driver->update([
        'availability' => $availability,
    ]);

    return true;
}
}