<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Friendship;
use App\Models\PrivateMessage;
use App\Events\PrivateMessageSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
            'type' => 'sometimes|in:text,image,file,system',
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
}
