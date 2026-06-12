<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DiscountCodeController extends Controller
{
    public function validateCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $discount = DiscountCode::where('code', $data['code'])->first();

        if (!$discount || !$discount->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'الكود غير صالح أو منتهي الصالحية',
            ], 422);
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
