<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\DriverBrowseService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DriverBrowseController extends Controller
{
    public function __construct(protected DriverBrowseService $driverBrowseService)
    {
    }

    /**
     * GET /api/customer/drivers/available
     */
    public function available(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'car_type_id' => ['nullable', 'exists:car_types,id'],
            'radius_km'   => ['nullable', 'numeric'],
        ]);

        $customer = $request->user();
        $drivers = $this->driverBrowseService->getAvailableDrivers($customer, $data);

        return response()->json([
            'success' => true,
            'data'    => $drivers,
        ]);
    }

    /**
     * GET /api/customer/drivers/{id}
     */
    public function show(int $id): JsonResponse
    {
        $driver = $this->driverBrowseService->getDriverDetails($id);

        return response()->json([
            'success' => true,
            'data'    => $driver,
        ]);
    }
}
