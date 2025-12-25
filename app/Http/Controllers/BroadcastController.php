<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

class BroadcastController extends Controller
{
    /**
     * 广播频道认证（使用 JWT）
     * Reverb/Pusher 客户端会调用此接口验证用户是否有权限订阅频道
     */
    public function authenticate(Request $request): JsonResponse
    {
        // 从 JWT 中间件获取用户 ID
        $userId = $request->attributes->get('jwt_user_id');
        
        if (!$userId) {
            return response()->json([
                'code' => 401,
                'message' => '未认证',
            ], 401);
        }

        $user = User::find($userId);
        
        if (!$user) {
            return response()->json([
                'code' => 401,
                'message' => '用户不存在',
            ], 401);
        }

        // 设置当前用户以供 Broadcast::channel 使用
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        // 处理 Presence Channel (presence-xxx) 和 Private Channel (private-xxx)
        try {
            // 使用 Laravel 内置的频道认证
            $response = Broadcast::auth($request);
            
            // Laravel 12 返回数组，早期版本返回 Response
            if (is_array($response)) {
                return response()->json($response);
            }
            
            return response()->json(json_decode($response->getContent(), true));
        } catch (\Exception $e) {
            return response()->json([
                'code' => 403,
                'message' => '频道认证失败: ' . $e->getMessage(),
            ], 403);
        }
    }
}
