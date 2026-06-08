<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Models\Ride;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Customer extends Authenticatable
{
    use HasApiTokens, Notifiable;
    public function rides(): HasMany
{
    return $this->hasMany(Ride::class);
}

public function ratings(): HasMany
{
    return $this->hasMany(Rating::class);
}
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
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