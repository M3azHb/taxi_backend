<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 use App\Models\Car;

class CarType extends Model
{
    protected $fillable = [
        'type_name',
        'base_fare',
        'price_per_km',
    'description',
    'is_active',
 ];

 protected $casts = [
    'base_fare' => 'decimal:2',
    'price_per_km' => 'decimal:2',
    'is_active' => 'boolean',
 ];

 public function cars()
 {
    return $this->hasMany(Car::class);
 }

 public function calculateFare(float $distanceKm)
{
    return $this->base_fare + ($distanceKm * $this->price_per_km);
}


}
