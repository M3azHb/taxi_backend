<?php

namespace App\Http\Middleware;

use App\Models\Driver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * يتأكد أن المستخدم المصادَق عليه (عبر توكن Sanctum) هو Driver مفعّل.
 * يُستخدم كـ alias: verified.driver
 */
class EnsureDriver
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Driver) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرّح لك بالوصول (حساب سائق مطلوب)',
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
