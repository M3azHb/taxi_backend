<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Ride;
use Illuminate\Database\Eloquent\Factories\Factory;

class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'driver_id'   => Driver::factory(),
            'ride_id'     => Ride::factory(),
            'score'       => fake()->numberBetween(1, 5),
            'comment'     => fake()->sentence(),
            'created_at'  => now(),
        ];
    }
}
