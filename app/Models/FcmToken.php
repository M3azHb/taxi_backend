<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FcmToken extends Model
{
    protected $fillable = ['user_id', 'user_type', 'token', 'device_type'];

    public function user(): MorphTo
    {
        return $this->morphTo();
    }
}
