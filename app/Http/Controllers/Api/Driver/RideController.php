<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\RideService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RideController extends Controller
{
    public function __construct(protected RideService $rideService)
    {
    }

    /**
     * GET /api/driver/rides/pending
     */
    public function pending(Request $request): JsonResponse
    {
        $rides = $this->rideService->getPendingRidesForDriver($request->user());

        return response()->json([
            'success' => true,
            'data'    => $rides,
        ]);
    }

    /**
     * GET /api/driver/rides/active
     */
    public function active(Request $request): JsonResponse
    {
        $ride = $this->rideService->getActiveRideForDriver($request->user());

        return response()->json([
            'success' => true,
            'data'    => $ride,
        ]);
    }

    /**
     * GET /api/driver/rides/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $ride = $this->rideService->getRideForDriver($request->user(), $id);

        return response()->json([
            'success' => true,
            'data'    => $ride,
        ]);
    }

    /**
     * GET /api/driver/rides (history)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status'   => ['nullable', 'in:all,completed,cancelled'],
            'page'     => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $rides = $this->rideService->getRideHistoryForDriver($request->user(), $filters);

        return response()->json([
            'success'    => true,
            'data'       => $rides->items(),
            'pagination' => [
                'current_page' => $rides->currentPage(),
                'last_page'    => $rides->lastPage(),
                'total'        => $rides->total(),
            ],
        ]);
    }

    /**
     * PUT /api/driver/rides/{id}/accept
     */
    public function accept(Request $request, int $id): JsonResponse
    {
        try {
            $ride = $this->rideService->acceptRide($request->user(), $id);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم قبول الرحلة',
            'data'    => $ride,
        ]);
    }

    /**
     * PUT /api/driver/rides/{id}/reject
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $this->rideService->rejectRide($request->user(), $id, $data['reason'] ?? null);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفض الرحلة',
        ]);
    }

    /**
     * PUT /api/driver/rides/{id}/arrived
     */
    public function arrived(Request $request, int $id): JsonResponse
    {
        try {
            $ride = $this->rideService->markDriverArrived($request->user(), $id);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحالة',
            'data'    => $ride,
        ]);
    }

    /**
     * PUT /api/driver/rides/{id}/start
     */
    public function start(Request $request, int $id): JsonResponse
    {
        try {
            $ride = $this->rideService->startRide($request->user(), $id);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم بدء الرحلة',
            'data'    => $ride,
        ]);
    }

    /**
     * PUT /api/driver/rides/{id}/complete
     */
    public function complete(Request $request, int $id, PaymentService $paymentService): JsonResponse
    {
        // السعر مُثبَّت من لحظة الحجز، فلم تعد المسافة القادمة من التطبيق تؤثّر
        // على الأجرة إطلاقاً — نقبلها اختيارياً فقط (للتوافق مع النسخ القديمة).
        $data = $request->validate([
            'distance_km'      => ['nullable', 'numeric'],
            'duration_minutes' => ['nullable', 'integer'],
        ]);

        try {
            $result = $this->rideService->completeRide($request->user(), $id, $data, $paymentService);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إنهاء الرحلة',
            'data'    => [
                'ride'    => $result['ride'],
                'payment' => [
                    'amount'             => $result['payment']->amount,
                    'commission_amount'  => $result['payment']->commission_amount,
                    'driver_earning'     => $result['payment']->driver_earning,
                    'status'             => $result['payment']->status,
                ],
            ],
        ]);
    }

    /**
     * PUT /api/driver/rides/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $this->rideService->cancelRideByDriver($request->user(), $id, $data['reason'] ?? null);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الرحلة',
        ]);
    }

    /**
     * POST /api/driver/rides/{id}/tracking
     */
    public function tracking(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        try {
            $this->rideService->recordTrackingPoint($request->user(), $id, $data);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
