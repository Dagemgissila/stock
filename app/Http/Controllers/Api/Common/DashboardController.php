<?php
namespace App\Http\Controllers\Api\Common;
use App\Http\Controllers\ApiBaseController;
use Illuminate\Http\JsonResponse;

class DashboardController extends ApiBaseController {
    public function notifications(): JsonResponse {
        $user          = auth('api')->user();
        $notifications = $user->notifications()->latest()->limit(20)->get();
        return $this->sendResponse([
            'unread_count'  => $user->unreadNotifications->count(),
            'notifications' => $notifications,
        ]);
    }
    public function markRead(int $id): JsonResponse {
        auth('api')->user()->notifications()->findOrFail($id)->markAsRead();
        return $this->sendResponse([],'Notification marked as read');
    }
    public function markAllRead(): JsonResponse {
        auth('api')->user()->unreadNotifications->markAsRead();
        return $this->sendResponse([],'All notifications marked as read');
    }
}
