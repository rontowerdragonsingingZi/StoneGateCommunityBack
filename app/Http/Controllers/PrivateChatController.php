<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Friendship;
use App\Models\PrivateMessage;
use App\Events\PrivateMessageSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PrivateChatController extends Controller
{
    /**
     * 发送私聊消息
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'friend_id' => 'required|integer|exists:users,id',
            'content' => 'required|string|max:2000',
            'type' => 'sometimes|in:text,image,file,sticker,system',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $friendId = $validated['friend_id'];
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '用户不存在',
            ], 401);
        }

        // 验证是否为好友
        if (!Friendship::areFriends($userId, $friendId)) {
            return response()->json([
                'code' => 403,
                'message' => '只能给好友发送私聊消息',
            ], 403);
        }

        // 生成会话ID
        $conversationId = PrivateMessage::makeConversationId($userId, $friendId);

        // 创建消息
        $message = PrivateMessage::create([
            'conversation_id' => $conversationId,
            'sender_id' => $userId,
            'receiver_id' => $friendId,
            'content' => $validated['content'],
            'type' => $validated['type'] ?? 'text',
        ]);

        // 广播消息
        broadcast(new PrivateMessageSent($message, $user))->toOthers();

        return response()->json([
            'code' => 201,
            'message' => 'D-Mail已发送',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'content' => $message->content,
                'type' => $message->type,
                'created_at' => $message->created_at->toIso8601String(),
                'sender' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                ],
            ],
        ], 201);
    }

    /**
     * 获取私聊历史消息
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'friend_id' => 'required|integer|exists:users,id',
            'before_id' => 'sometimes|integer',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $friendId = $validated['friend_id'];

        // 验证是否为好友
        if (!Friendship::areFriends($userId, $friendId)) {
            return response()->json([
                'code' => 403,
                'message' => '只能查看好友的聊天记录',
            ], 403);
        }

        $conversationId = PrivateMessage::makeConversationId($userId, $friendId);
        $limit = $validated['limit'] ?? 50;
        $beforeId = $validated['before_id'] ?? null;

        $query = PrivateMessage::where('conversation_id', $conversationId)
            ->with('sender:id,name,avatar')
            ->orderBy('id', 'desc');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->limit($limit)->get();

        // 标记消息为已读
        PrivateMessage::where('conversation_id', $conversationId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // 转换格式并反转顺序（最早的在前）
        $items = $messages->reverse()->values()->map(function ($msg) {
            return [
                'id' => $msg->id,
                'content' => $msg->content,
                'type' => $msg->type,
                'created_at' => $msg->created_at->toIso8601String(),
                'sender' => $msg->sender ? [
                    'id' => $msg->sender->id,
                    'name' => $msg->sender->name,
                    'avatar' => $msg->sender->avatar,
                ] : null,
            ];
        });

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => [
                'items' => $items,
                'has_more' => $messages->count() === $limit,
            ],
        ]);
    }

    /**
     * 获取最近会话列表（用于转发面板）
     */
    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        // 获取所有好友，并附带最近一条消息
        $friends = Friendship::where('user_id', $userId)
            ->where('status', 'accepted')
            ->with('friend:id,name,avatar')
            ->get();

        $conversations = $friends->map(function ($friendship) use ($userId) {
            $conversationId = PrivateMessage::makeConversationId($userId, $friendship->friend_id);
            
            // 获取最近一条消息
            $lastMessage = PrivateMessage::where('conversation_id', $conversationId)
                ->orderBy('id', 'desc')
                ->first();

            // 获取未读消息数
            $unreadCount = PrivateMessage::where('conversation_id', $conversationId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->count();

            return [
                'friend' => [
                    'id' => $friendship->friend->id,
                    'name' => $friendship->friend->name,
                    'avatar' => $friendship->friend->avatar,
                ],
                'last_message' => $lastMessage ? [
                    'content' => $lastMessage->content,
                    'type' => $lastMessage->type,
                    'created_at' => $lastMessage->created_at->toIso8601String(),
                ] : null,
                'unread_count' => $unreadCount,
            ];
        })->sortByDesc(function ($conv) {
            return $conv['last_message']['created_at'] ?? '1970-01-01';
        })->values();

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => $conversations,
        ]);
    }

    /**
     * 转发帖子给好友
     */
    public function forwardPost(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'friend_ids' => 'required|array|min:1|max:10',
            'friend_ids.*' => 'integer|exists:users,id',
            'post_id' => 'required|integer|exists:posts,id',
            'message' => 'nullable|string|max:500',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $user = User::find($userId);
        $post = Post::with('user:id,name')->find($validated['post_id']);

        if (!$user || !$post) {
            return response()->json([
                'code' => 404,
                'message' => '用户或帖子不存在',
            ], 404);
        }

        $successCount = 0;
        $failedFriends = [];

        foreach ($validated['friend_ids'] as $friendId) {
            // 验证是否为好友
            if (!Friendship::areFriends($userId, $friendId)) {
                $failedFriends[] = $friendId;
                continue;
            }

            $conversationId = PrivateMessage::makeConversationId($userId, $friendId);

            // 构建帖子卡片消息内容（JSON格式）
            $content = json_encode([
                'post_id' => $post->id,
                'title' => $post->title,
                'content' => mb_substr($post->content, 0, 100) . (mb_strlen($post->content) > 100 ? '...' : ''),
                'cover' => $post->cover,
                'author' => $post->user->name ?? 'Unknown',
                'message' => $validated['message'] ?? null,
            ], JSON_UNESCAPED_UNICODE);

            $message = PrivateMessage::create([
                'conversation_id' => $conversationId,
                'sender_id' => $userId,
                'receiver_id' => $friendId,
                'content' => $content,
                'type' => 'post_share',
            ]);

            // 广播消息
            broadcast(new PrivateMessageSent($message, $user))->toOthers();
            $successCount++;
        }

        // 更新帖子转发数
        $post->increment('share_count', $successCount);

        return response()->json([
            'code' => 200,
            'message' => "已转发给 {$successCount} 位好友",
            'data' => [
                'success_count' => $successCount,
                'failed_friends' => $failedFriends,
            ],
        ]);
    }
}
