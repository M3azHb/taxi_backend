<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * Unified method used across the whole project to create (and later push)
     * a notification for a Customer or a Driver.
     *
     * Called by RideService after every status change (accept, reject, arrived,
     * start, complete, cancel, payment confirmed, new rating, ...).
     */
    public function send(Model $user, string $type, string $title, string $body, array $data = []): Notification
    {
        $notification = Notification::create([
            'notifiable_id'   => $user->id,
            'notifiable_type' => $user->getMorphClass(),
            'type'            => $type,
            'title'           => $title,
            'body'            => $body,
            'data'            => $data,
        ]);

        // TODO: dispatch FCM push notification (later)
        // $this->sendPush($user, $title, $body, $data);

        return $notification;
    }

    /**
     * Get paginated notifications for a user (Customer or Driver).
     */
    public function getNotificationsForUser(Model $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get the count of unread notifications for a user.
     */
    public function getUnreadCount(Model $user): int
    {
        return $user->notifications()->unread()->count();
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Model $user, int $notifId): bool
    {
        $notif = $user->notifications()->findOrFail($notifId);

        $notif->markAsRead();

        return true;
    }

    /**
     * Mark all notifications of a user as read in a single query.
     * Returns the number of updated notifications.
     */
    public function markAllAsRead(Model $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Register / update an FCM token for a Driver (or Customer) device.
     *
     * Note: requires `fcm_tokens` table (user_id, user_type, token, device_type).
     */
    public function registerFcmToken(Model $user, string $token, string $deviceType): bool
    {
        FcmToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id'     => $user->id,
                'user_type'   => $user->getMorphClass(),
                'device_type' => $deviceType,
            ]
        );

        return true;
    }
}
