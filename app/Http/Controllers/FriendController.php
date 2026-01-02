<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FriendController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * 获取好友列表
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $friends = Friendship::where('user_id', $userId)
            ->where('status', 'accepted')
            ->with('friend:id,name,avatar')
            ->get()
            ->map(function ($friendship) {
                return [
                    'id' => $friendship->friend->id,
                    'name' => $friendship->friend->name,
                    'avatar' => $friendship->friend->avatar,
                    'added_at' => $friendship->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $friends,
        ]);
    }

    /**
     * 发送好友请求
     */
    public function sendRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'friend_id' => 'required|integer|exists:users,id',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $friendId = $validated['friend_id'];

        // 不能添加自己
        if ($userId == $friendId) {
            return response()->json([
                'code' => 400,
                'message' => '不能添加自己为好友',
            ], 400);
        }

        // 检查是否已经是好友
        if (Friendship::areFriends($userId, $friendId)) {
            return response()->json([
                'code' => 400,
                'message' => '已经是好友了',
            ], 400);
        }

        // 检查是否已发送请求
        if (Friendship::hasPendingRequest($userId, $friendId)) {
            return response()->json([
                'code' => 400,
                'message' => '已发送过好友请求，请等待对方确认',
            ], 400);
        }

        // 检查对方是否已向我发送请求（如果是，直接互相成为好友）
        $reverseRequest = Friendship::where('user_id', $friendId)
            ->where('friend_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($reverseRequest) {
            // 对方已发送请求，直接建立双向好友关系
            DB::transaction(function () use ($reverseRequest, $userId, $friendId) {
                $reverseRequest->update(['status' => 'accepted']);
                Friendship::create([
                    'user_id' => $userId,
                    'friend_id' => $friendId,
                    'status' => 'accepted',
                ]);
            });

            return response()->json([
                'code' => 0,
                'message' => '对方也向你发送了请求，已自动成为好友',
            ]);
        }

        // 创建好友请求
        $friendship = Friendship::create([
            'user_id' => $userId,
            'friend_id' => $friendId,
            'status' => 'pending',
        ]);

        // 发送通知
        $this->notificationService->notifyFriendRequest($friendship, $userId, $friendId);

        return response()->json([
            'code' => 0,
            'message' => '好友请求已发送',
        ]);
    }

    /**
     * 获取待处理的好友请求（收到的）
     */
    public function getRequests(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $requests = Friendship::where('friend_id', $userId)
            ->where('status', 'pending')
            ->with('user:id,name,avatar')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($friendship) {
                return [
                    'id' => $friendship->id,
                    'user' => [
                        'id' => $friendship->user->id,
                        'name' => $friendship->user->name,
                        'avatar' => $friendship->user->avatar,
                    ],
                    'created_at' => $friendship->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $requests,
        ]);
    }

    /**
     * 接受好友请求
     */
    public function acceptRequest(Request $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $friendship = Friendship::where('id', $id)
            ->where('friend_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json([
                'code' => 404,
                'message' => '好友请求不存在或已处理',
            ], 404);
        }

        // 建立双向好友关系
        DB::transaction(function () use ($friendship, $userId) {
            $friendship->update(['status' => 'accepted']);
            Friendship::create([
                'user_id' => $userId,
                'friend_id' => $friendship->user_id,
                'status' => 'accepted',
            ]);
        });

        return response()->json([
            'code' => 0,
            'message' => '已接受好友请求',
        ]);
    }

    /**
     * 拒绝好友请求
     */
    public function rejectRequest(Request $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $friendship = Friendship::where('id', $id)
            ->where('friend_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json([
                'code' => 404,
                'message' => '好友请求不存在或已处理',
            ], 404);
        }

        $friendship->delete();

        return response()->json([
            'code' => 0,
            'message' => '已拒绝好友请求',
        ]);
    }

    /**
     * 删除好友
     */
    public function destroy(Request $request, int $friendId): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        // 删除双向好友关系
        $deleted = Friendship::where(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $userId)->where('friend_id', $friendId);
        })->orWhere(function ($query) use ($userId, $friendId) {
            $query->where('user_id', $friendId)->where('friend_id', $userId);
        })->delete();

        if ($deleted === 0) {
            return response()->json([
                'code' => 404,
                'message' => '好友关系不存在',
            ], 404);
        }

        return response()->json([
            'code' => 0,
            'message' => '已删除好友',
        ]);
    }

    /**
     * 搜索用户（用于添加好友）
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:50',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $query = $validated['q'];

        $users = User::where('name', 'like', "%{$query}%")
            ->where('id', '!=', $userId)
            ->select('id', 'name', 'avatar')
            ->limit(20)
            ->get()
            ->map(function ($user) use ($userId) {
                // 检查好友状态
                $friendship = Friendship::where('user_id', $userId)
                    ->where('friend_id', $user->id)
                    ->first();

                $status = 'none';
                if ($friendship) {
                    $status = $friendship->status;
                } else {
                    // 检查对方是否向我发送了请求
                    $reverse = Friendship::where('user_id', $user->id)
                        ->where('friend_id', $userId)
                        ->where('status', 'pending')
                        ->exists();
                    if ($reverse) {
                        $status = 'incoming';
                    }
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'friendship_status' => $status,
                ];
            });

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $users,
        ]);
    }
}
