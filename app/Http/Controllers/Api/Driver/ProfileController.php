<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Services\CarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(protected CarService $carService)
    {
    }

    /**
     * GET /api/driver/profile  (محمي)
     */
    public function show(Request $request): JsonResponse
    {
        $driver = $request->user();
        $car    = $this->carService->getDriverCar($driver);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $driver->id,
                'name'           => $driver->name,
                'email'          => $driver->email,
                'phone'          => $driver->phone,
                'availability'   => $driver->availability,
                'rating_average' => (float) $driver->rating_average,
                'rating_count'   => (int) $driver->rating_count,
                'car'            => $car,
                'stats'          => [
                    'total_rides'     => $driver->rides()->count(),
                    'completed_rides' => $driver->rides()->where('status', Ride::STATUS_COMPLETED)->count(),
                ],
            ],
        ]);
    }

    /**
     * PUT /api/driver/profile  (محمي)
     */
    public function update(Request $request): JsonResponse
    {
        $driver = $request->user();

        $data = $request->validate([
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:drivers,email,' . $driver->id],
            'phone' => ['sometimes', 'string', 'max:30', 'unique:drivers,phone,' . $driver->id],
        ]);

        $driver->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الملف الشخصي',
            'data'    => [
                'id'    => $driver->id,
                'name'  => $driver->name,
                'email' => $driver->email,
                'phone' => $driver->phone,
            ],
        ]);
    }
}
