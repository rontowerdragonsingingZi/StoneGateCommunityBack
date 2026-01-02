<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * 获取通知列表
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');
        $type = $request->query('type'); // 可选：按类型筛选
        $limit = min($request->query('limit', 20), 50);
        $page = $request->query('page', 1);

        $query = Notification::where('user_id', $userId)
            ->with(['sender:id,name,avatar'])
            ->orderBy('created_at', 'desc');

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => [
                'items' => $notifications->items(),
                'total' => $notifications->total(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'unread_count' => Notification::where('user_id', $userId)->unread()->count(),
            ],
        ]);
    }

    /**
     * 获取未读通知数量
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        // 按类型统计未读数量
        $counts = Notification::where('user_id', $userId)
            ->unread()
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $total = array_sum($counts);

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => [
                'total' => $total,
                'by_type' => [
                    'like' => ($counts[Notification::TYPE_POST_LIKE] ?? 0) + ($counts[Notification::TYPE_COMMENT_LIKE] ?? 0),
                    'comment' => ($counts[Notification::TYPE_COMMENT] ?? 0) + ($counts[Notification::TYPE_COMMENT_REPLY] ?? 0),
                    'share' => $counts[Notification::TYPE_POST_SHARE] ?? 0,
                    'message' => $counts[Notification::TYPE_PRIVATE_MESSAGE] ?? 0,
                    'friend' => $counts[Notification::TYPE_FRIEND_REQUEST] ?? 0,
                ],
            ],
        ]);
    }

    /**
     * 标记单个或多个通知为已读
     */
    public function read(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:notifications,id',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $ids = $request->input('ids');

        $updated = Notification::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'code' => 200,
            'message' => '已标记为已读',
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }

    /**
     * 标记所有通知为已读
     */
    public function readAll(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');
        $type = $request->input('type'); // 可选：只标记某类型

        $query = Notification::where('user_id', $userId)->unread();

        if ($type) {
            $query->where('type', $type);
        }

        $updated = $query->update(['read_at' => now()]);

        return response()->json([
            'code' => 200,
            'message' => '已全部标记为已读',
            'data' => [
                'updated_count' => $updated,
            ],
        ]);
    }

    /**
     * 删除通知
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $notification = Notification::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'code' => 404,
                'message' => '通知不存在',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'code' => 200,
            'message' => '已删除通知',
        ]);
    }
}
