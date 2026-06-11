<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use App\Models\Ride;
use App\Models\Rating;
use Illuminate\Database\Eloquent\Relations\HasMany;

use Illuminate\Database\Eloquent\Relations\MorphMany;


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
