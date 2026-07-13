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

    public function blocker(): MorphTo
    {
        return $this->morphTo();
    }


    public function blocked(): MorphTo
    {
        return $this->morphTo();
    }



    public static function isBlocked(Model $blocker, Model $blocked): bool
    {
        return static::where('blocker_id', $blocker->id)
            ->where('blocker_type', $blocker->getMorphClass())
            ->where('blocked_id', $blocked->id)
            ->where('blocked_type', $blocked->getMorphClass())
            ->exists();
    }

    public static function isBlockedEither(Model $userA, Model $userB): bool
    {
        return static::isBlocked($userA, $userB)
            || static::isBlocked($userB, $userA);
    }
}
