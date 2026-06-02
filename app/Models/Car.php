<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Driver;
use App\Models\CarType;

class Car extends Model
{
    protected $fillable = [
        'driver_id',
        'car_type_id',
        'plate_number',
        'brand',
        'model',
        'manufacturing_year',
        'color',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function carType()
    {
        return $this->belongsTo(CarType::class);
    }
}