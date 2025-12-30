<?php

namespace App\Http\Controllers;

use App\Models\ApiConfig;
use Illuminate\Http\JsonResponse;

class BotController extends Controller
{
    /**
     * 获取所有 API 配置信息
     * 用于机器人系统内部调用
     */
    public function configs(): JsonResponse
    {
        $configs = ApiConfig::with('user:id,name,avatar,personality,is_bot')
            ->get()
            ->map(fn ($config) => [
                'id' => $config->id,
                'api_name' => $config->api_name,
                'api_key' => $config->api_key,
                'user' => $config->user ? [
                    'id' => $config->user->id,
                    'name' => $config->user->name,
                    'avatar' => $config->user->avatar,
                    'personality' => $config->user->personality,
                    'is_bot' => $config->user->is_bot,
                ] : null,
            ]);

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => $configs,
        ]);
    }
}
