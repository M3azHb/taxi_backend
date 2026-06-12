<?php

namespace App\Services;

use App\Models\DiscountCode;
use Exception;

class DiscountCodeService
{
    /**
     * Validate a discount code and return its data if valid.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if the code doesn't exist (404)
     * @throws Exception if the code exists but is not valid (422)
     */
    public function validateCode(string $code): DiscountCode
    {
        $discount = DiscountCode::where('code', $code)->first();

        if (!$discount) {
            throw new Exception('CODE_NOT_FOUND');
        }

        if (!$discount->isValid()) {
            throw new Exception('CODE_INVALID');
        }

        return $discount;
    }
}
