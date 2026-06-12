<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {
    }

    /**
     * POST /api/driver/reports
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ride_id'     => ['nullable', 'exists:rides,id'],
            'reported_id' => ['required', 'exists:customers,id'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $driver = $request->user();

        $report = $this->reportService->submitReportByDriver($driver, $data);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال البالغ، ستتم مراجعته',
            'data'    => [
                'id'     => $report->id,
                'status' => $report->status,
            ],
        ]);
    }

    /**
     * GET /api/driver/reports
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        $reports = $this->reportService->getReportsByDriver($driver);

        return response()->json([
            'success' => true,
            'data'    => $reports,
        ]);
    }
}
