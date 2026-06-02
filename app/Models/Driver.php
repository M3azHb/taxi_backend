<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Models\Car;
use App\Models\DriverLocation;

class Driver extends Authenticatable
{
    use HasApiTokens, Notifiable;

    const AVAILABILITY_ONLINE = 'online';
    const AVAILABILITY_OFFLINE = 'offline';
    const AVAILABILITY_BUSY = 'busy';
    public function isAvailable()
{
    return $this->is_active
        && $this->availability === self::AVAILABILITY_ONLINE;
}
    public function scopeOnline($query)
{
    return $query->where('is_active', true)
                 ->where('availability', self::AVAILABILITY_ONLINE);
}
public function cars()
{
    return $this->hasMany(Car::class);
}

public function location()
{
    return $this->hasOne(DriverLocation::class);
}
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'availability',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed'
    ];
}
