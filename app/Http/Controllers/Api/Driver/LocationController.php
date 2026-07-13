<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LocationService;

class LocationController extends Controller
{
    public function update(Request $request, LocationService $locationService)
    {
        $validated = $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'heading'   => 'nullable|numeric|between:0,360',
            'speed'     => 'nullable|numeric|min:0',
        ]);

        $locationService->updateDriverLocation(
            $request->user(),
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الموقع',
        ]);
    }
}