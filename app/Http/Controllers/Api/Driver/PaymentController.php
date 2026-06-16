<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService)
    {
    }

    /**
     * PUT /api/driver/rides/{id}/payment/confirm
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        try {
            $payment = $this->paymentService->confirmCashPayment($request->user(), $id);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد استلام المبلغ',
            'data'    => [
                'status'  => $payment->status,
                'paid_at' => $payment->paid_at,
            ],
        ]);
    }

    /**
     * GET /api/driver/payments
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page'     => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $payments = $this->paymentService->getPaymentHistoryForDriver($request->user(), $filters);

        return response()->json([
            'success'    => true,
            'data'       => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'total'        => $payments->total(),
            ],
        ]);
    }
}
