<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    /**
     * GET /api/customer/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);

        $notifications = $this->notificationService->getNotificationsForUser($request->user(), $perPage);

        return response()->json([
            'success'    => true,
            'data'       => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    /**
     * GET /api/customer/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return response()->json([
            'success' => true,
            'data'    => ['count' => $count],
        ]);
    }

    /**
     * PUT /api/customer/notifications/{id}/read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $this->notificationService->markAsRead($request->user(), $id);

        return response()->json([
            'success' => true,
            'message' => 'تم التحديث',
        ]);
    }

    /**
     * PUT /api/customer/notifications/read-all
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return response()->json([
            'success' => true,
            'message' => 'تم تعليم كل الإشعارات كمقروءة',
        ]);
    }
}
