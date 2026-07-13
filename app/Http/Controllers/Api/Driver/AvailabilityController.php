<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CarService;

class AvailabilityController extends Controller
{
    public function update(Request $request, CarService $carService)
    {
        $validated = $request->validate([
            'availability' => 'required|in:online,offline',
        ]);

        try {
            $carService->updateAvailability(
                $request->user(),
                $validated['availability']
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة',
                'data' => [
                    'availability' => $validated['availability'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}