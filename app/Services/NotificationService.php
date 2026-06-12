<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\FcmToken;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function getNotificationsForUser(Model $user, int $perPage): LengthAwarePaginator
    {
        return $user->notifications()->latest()->paginate($perPage); // استخدام الـ Pagination الجاهز
    }

    public function getUnreadCount(Model $user): int
    {
        return $user->notifications()->unread()->count();
    }

    public function markAsRead(Model $user, int $notifId): bool
    {
        $notif = $user->notifications()->findOrFail($notifId);
        $notif->markAsRead();
        return true;
    }

    public function markAllAsRead(Model $user): int
    {
        // استخدام تحديث مباشر بـ Query واحد بدلاً من الـ Loop للأداء
        return $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function registerFcmToken(Driver $driver, string $token, string $deviceType): bool
    {
        return FcmToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id'     => $driver->id,
                'user_type'   => Driver::class,
                'device_type' => $deviceType
            ]
        );
    }

    public function send(Model $user, string $type, string $title, string $body, array $data = []): Notification
    {
        return Notification::create([
            'notifiable_id'   => $user->id,
            'notifiable_type' => $user->getMorphClass(),
            'type'            => $type,
            'title'           => $title,
            'body'            => $body,
            'data'            => $data,
        ]);

    }
}
