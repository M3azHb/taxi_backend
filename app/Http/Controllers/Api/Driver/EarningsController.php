<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Services\EarningsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EarningsController extends Controller
{
    public function __construct(protected EarningsService $earningsService)
    {
    }

    /**
     * GET /api/driver/earnings/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:today,week,month,all'],
        ]);

        $summary = $this->earningsService->getSummary($request->user(), $data['period'] ?? 'all');

        return response()->json([
            'success' => true,
            'data'    => $summary,
        ]);
    }

    /**
     * GET /api/driver/earnings/chart
     */
    public function chart(Request $request): JsonResponse
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:week,month'],
        ]);

        $chart = $this->earningsService->getChart($request->user(), $data['period'] ?? 'week');

        return response()->json([
            'success' => true,
            'data'    => $chart,
        ]);
    }

    /**
     * GET /api/driver/earnings/commission
     */
    public function commission(Request $request): JsonResponse
    {
        $data = $this->earningsService->getCommissionOwed($request->user());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
