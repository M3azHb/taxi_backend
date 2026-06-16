<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ride\RateRequest;
use App\Services\RideService;
use Exception;
use Illuminate\Http\JsonResponse;

class RatingController extends Controller
{
    public function __construct(protected RideService $rideService)
    {
    }

    /**
     * POST /api/customer/rides/{id}/rate
     */
    public function store(RateRequest $request, int $id): JsonResponse
    {
        try {
            $this->rideService->rateRide($request->user(), $id, $request->validated());
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال التقييم',
        ]);
    }
}
