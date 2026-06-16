<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Services\RideService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RatingController extends Controller
{
    public function __construct(protected RideService $rideService)
    {
    }

    /**
     * GET /api/driver/ratings
     */
    public function index(Request $request): JsonResponse
    {
        $ratings = $this->rideService->getDriverRatings($request->user());

        return response()->json([
            'success'    => true,
            'data'       => $ratings->items(),
            'pagination' => [
                'current_page' => $ratings->currentPage(),
                'last_page'    => $ratings->lastPage(),
                'total'        => $ratings->total(),
            ],
        ]);
    }

    /**
     * GET /api/driver/ratings/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $data = $this->rideService->getDriverRatingsSummary($request->user());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
