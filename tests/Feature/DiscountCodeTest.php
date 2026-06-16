<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DiscountCode;
use App\Models\User; // لا تنسي استيراد الـ User
use Illuminate\Foundation\Testing\RefreshDatabase;

class DiscountCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_discount_code_successfully()
    {
        // 1. Arrange: إنشاء مستخدم وتسجيل دخوله، وإنشاء كود خصم
        $user = User::factory()->create(); // إنشاء مستخدم
        $this->actingAs($user); // **هذه هي الخطوة المهمة: تسجيل الدخول**

        $discount = DiscountCode::factory()->create([
            'code' => 'SAVE50',
            'expiry_date' => now()->addDays(5),
            'is_active' => true
        ]);

        // 2. Act: إرسال الطلب
        $response = $this->postJson('/api/customer/discount/validate', ['code' => 'SAVE50']);

        // 3. Assert: التأكد من النتيجة
        $response->assertStatus(200)
                 ->assertJsonPath('data.code', 'SAVE50');
    }

}
