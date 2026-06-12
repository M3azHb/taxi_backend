<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\BlockService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BlockListController extends Controller
{
    public function __construct(protected BlockService $blockService)
    {
    }

    /**
     * GET /api/customer/blocks
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $blocks = $this->blockService->getBlocksByCustomer($customer);

        return response()->json([
            'success' => true,
            'data'    => $blocks,
        ]);
    }

    /**
     * POST /api/customer/blocks
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'driver_id' => ['required', 'exists:drivers,id'],
            'reason'    => ['nullable', 'string', 'max:255'],
        ]);

        $customer = $request->user();

        try {
            $this->blockService->blockDriver($customer, $data['driver_id'], $data['reason'] ?? null);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حظر السائق',
        ]);
    }

    /**
     * DELETE /api/customer/blocks/{driver_id}
     */
    public function destroy(Request $request, int $driver_id): JsonResponse
    {
        $customer = $request->user();

        $this->blockService->unblockDriver($customer, $driver_id);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الحظر',
        ]);
    }
}
