<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * خدمة مصادقة موحّدة تعمل لأي موديل (Customer أو Driver).
 * كلاهما يستخدم HasApiTokens ويملك حقول: phone, email, password, is_active.
 */
class AuthService
{
    /**
     * إنشاء حساب جديد وإرجاع المستخدم + التوكن.
     * كلمة المرور تُشفَّر تلقائياً عبر cast: 'password' => 'hashed'.
     */
    public function register(string $modelClass, array $attributes, string $tokenName): array
    {
        $user = $modelClass::create($attributes);

        return [
            'user'  => $user,
            'token' => $user->createToken($tokenName)->plainTextToken,
        ];
    }

    /**
     * تسجيل الدخول بالهاتف أو البريد + كلمة المرور.
     * يرمي ValidationException إذا كانت البيانات خاطئة.
     */
    public function login(string $modelClass, string $identifier, string $password, string $tokenName): array
    {
        $user = $modelClass::where('phone', $identifier)
            ->orWhere('email', $identifier)
            ->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => ['بيانات الدخول غير صحيحة'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'phone' => ['الحساب غير مفعّل'],
            ]);
        }

        return [
            'user'  => $user,
            'token' => $user->createToken($tokenName)->plainTextToken,
        ];
    }

    /**
     * تسجيل الخروج: حذف التوكن الحالي فقط.
     */
    public function logout($user): void
    {
        $user->currentAccessToken()->delete();
    }
}
