<?php

namespace App\Http\Controllers\Api\Driver;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
    }

    /**
     * POST /api/driver/register  (عام)
     * ينشئ الحساب ويُصدر رمز تحقق OTP (بدون توكن حتى يتم التحقق).
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:drivers,email'],
            'phone'    => ['required', 'string', 'max:30', 'unique:drivers,phone'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        Driver::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'phone'        => $data['phone'],
            'password'     => $data['password'],
            'availability' => Driver::AVAILABILITY_OFFLINE,
            'is_active'    => true,
        ]);

        $code = $this->issueOtp($data['phone']);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الحساب، أدخل رمز التحقق المرسل',
            'data'    => array_filter([
                'phone'    => $data['phone'],
                // في وضع التطوير فقط نُرجع الرمز (لا يوجد مزوّد SMS بعد)
                'dev_code' => config('app.debug') ? $code : null,
            ]),
        ], 201);
    }

    /**
     * POST /api/driver/verify-otp  (عام)
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string'],
            'code'  => ['required', 'string'],
        ]);

        $cached = Cache::get($this->otpKey($data['phone']));

        if (! $cached || (string) $cached !== (string) $data['code']) {
            throw ValidationException::withMessages([
                'code' => ['رمز التحقق غير صحيح أو منتهي'],
            ]);
        }

        Cache::forget($this->otpKey($data['phone']));

        $driver = Driver::where('phone', $data['phone'])->firstOrFail();
        $token  = $driver->createToken('driver')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم التحقق بنجاح',
            'data'    => [
                'token' => $token,
                'user'  => $this->payload($driver),
            ],
        ]);
    }

    /**
     * POST /api/driver/resend-otp  (عام)
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['phone' => ['required', 'string']]);

        $code = $this->issueOtp($data['phone']);

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة إرسال الرمز',
            'data'    => array_filter(['dev_code' => config('app.debug') ? $code : null]),
        ]);
    }

    /**
     * POST /api/driver/login  (عام)
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $result = $this->authService->login(Driver::class, $data['phone'], $data['password'], 'driver');

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
     * POST /api/driver/logout  (محمي)
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
     * GET /api/driver/me  (محمي)
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->payload($request->user()),
        ]);
    }

    // ─── Helpers ───

    private function issueOtp(string $phone): string
    {
        // رمز ثابت مؤقتاً: لم نربط مزوّد SMS بعد، فنستخدم "1234" للتجربة والعرض.
        // عند ربط SMS لاحقاً نُعيده لرمز عشوائي.
        $code = '1234';
        Cache::put($this->otpKey($phone), $code, now()->addMinutes(10));

        return $code;
    }

    private function otpKey(string $phone): string
    {
        return "otp:driver:{$phone}";
    }

    private function payload(Driver $driver): array
    {
        return [
            'id'             => $driver->id,
            'name'           => $driver->name,
            'email'          => $driver->email,
            'phone'          => $driver->phone,
            'availability'   => $driver->availability,
            'rating_average' => (float) $driver->rating_average,
            'rating_count'   => (int) $driver->rating_count,
        ];
    }
}
