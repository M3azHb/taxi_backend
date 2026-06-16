<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Driver;
use Symfony\Component\HttpFoundation\Response;

class CheckDriverAvailability
{
    public function handle(Request $request, Closure $next): Response
    {
        // نتحقق من أن المستخدم المسجل هو سائق
        $user = auth()->user();

        if ($user instanceof Driver) {
            // نتحقق من حالة السائق (هل هو متاح لاستقبال الطلبات؟)
            if (!$user->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، لا يمكنك تنفيذ هذا الإجراء لأنك غير متاح حالياً.'
                ], 403);
            }
        }

        return $next($request);
    }
}
