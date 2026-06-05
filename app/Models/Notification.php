<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    // Type Constants

    const TYPE_NEW_RIDE_REQUEST  = 'new_ride_request';
    const TYPE_RIDE_ACCEPTED     = 'ride_accepted';
    const TYPE_RIDE_REJECTED     = 'ride_rejected';
    const TYPE_DRIVER_ARRIVED    = 'driver_arrived';
    const TYPE_RIDE_STARTED      = 'ride_started';
    const TYPE_RIDE_COMPLETED    = 'ride_completed';
    const TYPE_RIDE_CANCELLED    = 'ride_cancelled';
    const TYPE_PAYMENT_CONFIRMED = 'payment_confirmed';
    const TYPE_NEW_RATING        = 'new_rating';
    const TYPE_DISCOUNT_CODE     = 'discount_code';
    const TYPE_SYSTEM            = 'system';

    //  Fillable

    protected $fillable = [
        'notifiable_id',
        'notifiable_type',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    //  Casts

    protected $casts = [
        'data'    => 'array',   // حرج جداً: بدونه يُحفظ JSON كـ string
        'read_at' => 'datetime',
    ];

    // ─── Relations ────────────────────────────────────────────────

    /**
     * صاحب الإشعار (Customer أو Driver).
     * اسم العلاقة يطابق بادئة الأعمدة: notifiable_id / notifiable_type
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Methods ──────────────────────────────────────────────────

    public function markAsRead(): void
    {

        if ($this->isUnread()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isRead(): bool
    {
        return ! is_null($this->read_at);
    }

    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * إنشاء إشعار وإرساله لمستخدم.
     * Factory method — يُغني عن تكرار الكود في كل Controller.
     */
    public static function send(
        Model  $notifiable,
        string $type,
        string $title,
        string $body,
        array  $data = []
    ): self {
        return static::create([
            'notifiable_id'   => $notifiable->id,
            'notifiable_type' => $notifiable->getMorphClass(),
            'type'            => $type,
            'title'           => $title,
            'body'            => $body,
            'data'            => $data,
        ]);
    }

    //  Scopes

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * إشعارات نوع معين.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }
}
