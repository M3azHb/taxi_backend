<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Rating;
use App\Models\Notification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. إنشاء المستخدم التجريبي
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // 2. إضافة التقييمات والإشعارات
        Rating::factory()->count(10)->create();
        Notification::factory()->count(10)->create();
    }
}
