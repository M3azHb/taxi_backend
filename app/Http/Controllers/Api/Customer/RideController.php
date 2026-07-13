<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ride\CreateRideRequest;
use App\Http\Requests\Ride\EstimateRequest;
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
     * POST /api/customer/rides/estimate
     */
    public function estimate(EstimateRequest $request): JsonResponse
    {
        $data = $this->rideService->estimateFare($request->validated());

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * POST /api/customer/rides
     */
    public function store(CreateRideRequest $request): JsonResponse
    {
        $customer = $request->user();

        try {
            $ride = $this->rideService->createRide($customer, $request->validated());
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الرحلة',
            'data'    => [
                'id'             => $ride->id,
                'status'         => $ride->status,
                'estimated_fare' => $ride->estimated_fare,
                'requested_at'   => $ride->requested_at,
                // لا يوجد سائق بعد — الطلب مبثوث ينتظر القبول.
                'driver'         => null,
            ],
        ], 201);
    }

    /**
     * GET /api/customer/rides/active
     */
    public function active(Request $request): JsonResponse
    {
        $ride = $this->rideService->getActiveRideForCustomer($request->user());

        return response()->json([
            'success' => true,
            'data'    => $ride,
        ]);
    }

    /**
     * GET /api/customer/rides/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $ride = $this->rideService->getRideForCustomer($request->user(), $id);

        return response()->json([
            'success' => true,
            'data'    => $ride,
        ]);
    }

    /**
     * GET /api/customer/rides (history)
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status'   => ['nullable', 'in:all,completed,cancelled'],
            'page'     => ['nullable', 'integer'],
            'per_page' => ['nullable', 'integer'],
        ]);

        $rides = $this->rideService->getRideHistoryForCustomer($request->user(), $filters);

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
     * PUT /api/customer/rides/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string'],
        ]);

        try {
            $this->rideService->cancelRideByCustomer($request->user(), $id, $data['reason'] ?? null);
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
     * GET /api/customer/rides/{id}/tracking
     */
    public function tracking(Request $request, int $id): JsonResponse
    {
        $data = $this->rideService->getRideTracking($request->user(), $id);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
