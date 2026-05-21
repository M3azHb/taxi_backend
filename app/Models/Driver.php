<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Driver extends Authenticatable
{
    use HasApiTokens, Notifiable;

    const AVAILABILITY_ONLINE = 'online';
    const AVAILABILITY_OFFLINE = 'offline';
    const AVAILABILITY_BUSY = 'busy';

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