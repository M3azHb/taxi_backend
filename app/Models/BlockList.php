<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BlockList extends Model
{
    protected $fillable = [
        'blocker_id',
        'blocker_type',
        'blocked_id',
        'blocked_type',
        'reason',
    ];

    // Relations

    /**
     * من قام بالحظر (Customer أو Driver).
     * اسم العلاقة يطابق بادئة الأعمدة: blocker_id / blocker_type
     */
    public function blocker(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * من تم حظره (Customer أو Driver).
     */
    public function blocked(): MorphTo
    {
        return $this->morphTo();
    }

    // Static Methods


    public static function isBlocked(Model $blocker, Model $blocked): bool
    {
        return static::where('blocker_id', $blocker->id)
            ->where('blocker_type', $blocker->getMorphClass())
            ->where('blocked_id', $blocked->id)
            ->where('blocked_type', $blocked->getMorphClass())
            ->exists();
    }

    /**
     * هل يوجد حظر في أي اتجاه بين المستخدمين؟
     * مفيد للتحقق قبل إتاحة الرحلة بين طرفين.
     */
    public static function isBlockedEither(Model $userA, Model $userB): bool
    {
        return static::isBlocked($userA, $userB)
            || static::isBlocked($userB, $userA);
    }
}
