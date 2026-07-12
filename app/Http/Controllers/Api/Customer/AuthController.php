<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:customers,email'],
            'phone' => ['required', 'string', 'unique:customers,phone'],
            'password' => ['required', 'min:8'],
        ]);

        $result = $this->authService->register($data);

        return response()->json([
            'success' => true,
            'message' => 'Customer registered successfully.',
            'data' => [
                'customer' => $result['customer'],
                'token' => $result['token'],
            ],
        ], 201);
    }
}