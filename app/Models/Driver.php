<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Models\Car;
use App\Models\DriverLocation;
use App\Models\Ride;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;


class Driver extends Authenticatable
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

// علاقة السيارة المفردة (كل سائق يملك سيارة واحدة في نظامنا) —
// يستخدمها DriverBrowseService في with(['car.carType']).
public function car()
{
    return $this->hasOne(Car::class);
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
        'is_active',
        'rating_average',
        'rating_count',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed'
    ];
    // ─── Relations Linked to Shahed's Work ───────────────────────

    // 1. البلاغات التي قدمها هذا المستخدم (المشتكي)
    public function submittedReports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reporter');
    }

    // 2. البلاغات المقدمة ضد هذا المستخدم (المشتكى عليه)
    public function receivedReports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reported');
    }

    // 3. الأشخاص الذين قام هذا المستخدم بحظرهم
    public function blockedUsers(): MorphMany
    {
        return $this->morphMany(BlockList::class, 'blocker');
    }

    // 4. الإشعارات التي استلمها هذا المستخدم
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
}
