<?php

namespace App\Http\Controllers\Api\Customer;

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
     * POST /api/customer/reports
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ride_id'     => ['nullable', 'exists:rides,id'],
            'reported_id' => ['required', 'exists:drivers,id'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $customer = $request->user();

        $report = $this->reportService->submitReportByCustomer($customer, $data);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال البلاغ، ستتم مراجعته',
            'data'    => [
                'id'     => $report->id,
                'status' => $report->status,
            ],
        ]);
    }

    /**
     * GET /api/customer/reports
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $reports = $this->reportService->getReportsByCustomer($customer);

        return response()->json([
            'success' => true,
            'data'    => $reports,
        ]);
    }
}
