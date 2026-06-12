<?php

namespace App\Http\Controllers\Api\Driver;

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
     * GET /api/driver/blocks
     */
    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        $blocks = $this->blockService->getBlocksByDriver($driver);

        return response()->json([
            'success' => true,
            'data'    => $blocks,
        ]);
    }

    /**
     * POST /api/driver/blocks
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'reason'      => ['nullable', 'string', 'max:255'],
        ]);

        $driver = $request->user();

        try {
            $this->blockService->blockCustomer($driver, $data['customer_id'], $data['reason'] ?? null);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حظر الزبون',
        ]);
    }

    /**
     * DELETE /api/driver/blocks/{customer_id}
     */
    public function destroy(Request $request, int $customer_id): JsonResponse
    {
        $driver = $request->user();

        $this->blockService->unblockCustomer($driver, $customer_id);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الحظر',
        ]);
    }
}
