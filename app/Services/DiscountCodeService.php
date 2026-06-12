<?php

namespace App\Services;

use App\Models\DiscountCode;

class DiscountCodeService
{

    public function validateCode(string $code): ?DiscountCode
    {
        return DiscountCode::where('code', $code)->first();
    }
}
