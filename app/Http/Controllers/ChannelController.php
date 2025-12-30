<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChannelController extends Controller
{
    /**
     * 获取频道列表（公开频道）
     */
    public function index(): JsonResponse
    {
        $channels = Channel::with('creator:id,name')
            ->where('is_private', false)
            ->orderByDesc('is_default')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $channels,
        ]);
    }

    /**
     * 获取我创建的频道
     */
    public function mine(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $channels = Channel::with('creator:id,name')
            ->where('creator_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $channels,
        ]);
    }

    /**
     * 创建新频道
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|regex:/^[a-z0-9_-]+$/|unique:channels,name',
            'display_name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_private' => 'boolean',
        ]);

        $channel = Channel::create([
            'name' => $validated['name'],
            'display_name' => $validated['display_name'],
            'description' => $validated['description'] ?? null,
            'creator_id' => $request->user()->id,
            'is_default' => false,
            'is_private' => $validated['is_private'] ?? false,
        ]);

        return response()->json([
            'code' => 0,
            'message' => 'Channel created successfully',
            'data' => $channel->load('creator:id,name'),
        ], 201);
    }

    /**
     * 获取单个频道详情
     */
    public function show(string $name): JsonResponse
    {
        $channel = Channel::with('creator:id,name')
            ->where('name', $name)
            ->first();

        if (!$channel) {
            return response()->json([
                'code' => 404,
                'message' => 'Channel not found',
            ], 404);
        }

        return response()->json([
            'code' => 0,
            'message' => 'success',
            'data' => $channel,
        ]);
    }

    /**
     * 更新频道公告（仅创建者可操作）
     */
    public function updateAnnouncement(Request $request, string $name): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $channel = Channel::where('name', $name)->first();

        if (!$channel) {
            return response()->json([
                'code' => 404,
                'message' => 'Channel not found',
            ], 404);
        }

        // 检查是否是创建者（默认频道允许任何人更新公告）
        if (!$channel->is_default && $channel->creator_id !== $userId) {
            return response()->json([
                'code' => 403,
                'message' => '只有频道创建者才能更新公告',
            ], 403);
        }

        $validated = $request->validate([
            'announcement' => 'nullable|string|max:1000',
        ]);

        $channel->update([
            'announcement' => $validated['announcement'] ?? null,
        ]);

        return response()->json([
            'code' => 0,
            'message' => '公告更新成功',
            'data' => $channel,
        ]);
    }
}
