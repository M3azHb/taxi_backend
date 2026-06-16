<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Ride;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'ride_id' => Ride::factory(),
            'type'    => fake()->randomElement(['ride_new_request', 'ride_accepted', 'ride_rejected']),
            'data'    => json_encode(['message' => 'تم تحديث حالة الرحلة']),
            'read_at' => null,
            'created_at' => now(),
        ];
    }
}
