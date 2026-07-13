<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يتأكد أن المستخدم المصادَق عليه (عبر توكن Sanctum) هو Customer مفعّل.
 * يُستخدم كـ alias: verified.customer
 */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرّح لك بالوصول (حساب عميل مطلوب)',
            ], 403);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير مفعّل',
            ], 403);
        }

        return $next($request);
    }
}
