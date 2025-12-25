<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChannelController extends Controller
{
    /**
     * 获取频道列表
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
}
