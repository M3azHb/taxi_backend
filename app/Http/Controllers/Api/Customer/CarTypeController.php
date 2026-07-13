<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\CarType;

class CarTypeController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => CarType::where('is_active', true)->get()
        ]);
    }
}