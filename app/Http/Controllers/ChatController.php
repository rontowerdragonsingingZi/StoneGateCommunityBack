<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    /**
     * 发送消息到指定频道
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'type' => 'sometimes|in:text,image,system',
            'channel' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '用户不存在',
                'data' => null,
            ], 401);
        }

        $channelName = $validated['channel'];

        // 创建消息
        $message = Message::create([
            'user_id' => $userId,
            'channel' => $channelName,
            'content' => $validated['content'],
            'type' => $validated['type'] ?? 'text',
        ]);

        // 广播消息
        broadcast(new MessageSent($message, $user))->toOthers();

        return response()->json([
            'code' => 201,
            'message' => 'D-Mail已发送至世界线',
            'data' => [
                'id' => $message->id,
                'content' => $message->content,
                'type' => $message->type,
                'created_at' => $message->created_at->toIso8601String(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                ],
            ],
        ], 201);
    }

    /**
     * 获取指定频道的历史消息
     */
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'channel' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/',
            'before_id' => 'sometimes|integer',
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $channelName = $request->input('channel');
        $limit = $request->input('limit', 50);
        $beforeId = $request->input('before_id');

        $query = Message::where('channel', $channelName)
            ->with('user:id,name,avatar')
            ->orderBy('id', 'desc');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->limit($limit)->get();

        // 转换格式并反转顺序（最早的在前）
        $items = $messages->reverse()->values()->map(function ($msg) {
            return [
                'id' => $msg->id,
                'content' => $msg->content,
                'type' => $msg->type,
                'created_at' => $msg->created_at->toIso8601String(),
                'user' => $msg->user ? [
                    'id' => $msg->user->id,
                    'name' => $msg->user->name,
                    'avatar' => $msg->user->avatar,
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
