<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\DiscountCodeService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiscountCodeController extends Controller
{
    public function __construct(protected DiscountCodeService $discountCodeService)
    {
    }

    /**
     * POST /api/customer/discount-codes/validate
     */
    public function validateCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        try {
            $discount = $this->discountCodeService->validateCode($data['code']);
        } catch (Exception $e) {
            $status = $e->getMessage() === 'CODE_NOT_FOUND' ? 404 : 422;

            return response()->json([
                'success' => false,
                'message' => 'الكود غير صالح أو منتهي الصالحية',
            ], $status);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'code'                => $discount->code,
                'discount_percentage' => $discount->discount_percentage,
                'expiry_date'         => $discount->expiry_date,
            ],
        ]);
    }
}
