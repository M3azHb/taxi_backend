<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    /**
     * POST /api/customer/register  (عام)
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:customers,email'],
            'phone'    => ['required', 'string', 'max:30', 'unique:customers,phone'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        $result = $this->authService->register(Customer::class, [
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'],
            'password'  => $data['password'],
            'is_active' => true,
        ], 'customer');

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'data'    => [
                'token' => $result['token'],
                'user'  => $this->payload($result['user']),
            ],
        ], 201);
    }

    /**
     * POST /api/customer/login  (عام)
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login(Customer::class, $data['phone'], $data['password'], 'customer');

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول',
            'data'    => [
                'token' => $result['token'],
                'user'  => $this->payload($result['user']),
            ],
        ]);
    }

    /**
     * POST /api/customer/logout  (محمي)
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج',
        ]);
    }

    /**
     * GET /api/customer/me  (محمي)
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->payload($request->user()),
        ]);
    }

    private function payload(Customer $customer): array
    {
        return [
            'id'    => $customer->id,
            'name'  => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
        ];
    }
}
