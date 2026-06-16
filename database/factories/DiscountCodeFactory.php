<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DiscountCode;

class DiscountCodeFactory extends Factory
{
    protected $model = DiscountCode::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('DISC-####'), // سينشئ أكواد مثل DISC-1234
            'discount_percentage' => 10,
            'expiry_date' => now()->addDays(10),
            'is_active' => true,
        ];
    }
}
