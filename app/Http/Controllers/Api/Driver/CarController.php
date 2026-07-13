<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Car\AddCarRequest;
use App\Http\Requests\Car\UpdateCarRequest;
use App\Services\CarService;
use Illuminate\Http\Request;

class CarController extends Controller
{
    public function show(Request $request, CarService $carService)
    {
        $car = $carService->getDriverCar($request->user());

        return response()->json([
            'success' => true,
            'data' => $car,
            'message' => $car ? null : 'لا توجد سيارة مسجلة',
        ]);
    }

    public function store(AddCarRequest $request, CarService $carService)
    {
        try {
            $car = $carService->addCarForDriver(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة السيارة',
                'data' => $car,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function update(UpdateCarRequest $request, CarService $carService)
    {
        try {
            $car = $carService->updateDriverCar(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث السيارة',
                'data' => $car,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}