<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Like;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    /**
     * 获取帖子列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::with(['user:id,name,avatar'])
            ->orderBy('created_at', 'desc');

        // 按标签筛选
        if ($request->has('tag') && $request->tag) {
            $query->where('tag', $request->tag);
        }

        // 按用户筛选
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $limit = min($request->get('limit', 20), 50);
        $posts = $query->paginate($limit);

        // 添加当前用户是否已点赞
        $userId = $request->attributes->get('jwt_user_id');
        $currentUser = $userId ? User::find($userId) : null;
        $posts->getCollection()->transform(function ($post) use ($currentUser) {
            $post->is_liked = $post->isLikedBy($currentUser);
            return $post;
        });

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => [
                'items' => $posts->items(),
                'total' => $posts->total(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
            ]
        ]);
    }

    /**
     * 获取帖子详情
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $post = Post::with(['user:id,name,avatar'])->find($id);

        if (!$post) {
            return response()->json([
                'code' => 404,
                'message' => '该观测日志不存在于此世界线'
            ], 404);
        }

        // 增加浏览量
        $post->incrementViewCount();

        // 检查当前用户是否已点赞
        $userId = $request->attributes->get('jwt_user_id');
        $currentUser = $userId ? User::find($userId) : null;
        $post->is_liked = $post->isLikedBy($currentUser);

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => $post
        ]);
    }

    /**
     * 创建帖子
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'cover' => 'nullable|string|max:500',
            'tag' => 'nullable|string|max:50',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $validated['user_id'] = $userId;
        $validated['tag'] = $validated['tag'] ?? 'GENERAL';

        $post = Post::create($validated);
        $post->load('user:id,name,avatar');

        return response()->json([
            'code' => 201,
            'message' => '观测日志已记录至世界线',
            'data' => $post
        ], 201);
    }

    /**
     * 更新帖子
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json([
                'code' => 404,
                'message' => '该观测日志不存在于此世界线'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id');
        if ($post->user_id !== $userId) {
            return response()->json([
                'code' => 403,
                'message' => '无权修改他人的观测日志'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:200',
            'content' => 'sometimes|string',
            'cover' => 'nullable|string|max:255',
            'tag' => 'nullable|string|max:50',
        ]);

        $post->update($validated);
        $post->load('user:id,name,avatar');

        return response()->json([
            'code' => 200,
            'message' => '观测日志已更新',
            'data' => $post
        ]);
    }

    /**
     * 删除帖子
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json([
                'code' => 404,
                'message' => '该观测日志不存在于此世界线'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id');
        if ($post->user_id !== $userId) {
            return response()->json([
                'code' => 403,
                'message' => '无权删除他人的观测日志'
            ], 403);
        }

        $post->delete();

        return response()->json([
            'code' => 200,
            'message' => '观测日志已从世界线中移除'
        ]);
    }

    /**
     * 点赞/取消点赞
     */
    public function toggleLike(Request $request, int $id): JsonResponse
    {
        $post = Post::find($id);
        if (!$post) {
            return response()->json([
                'code' => 404,
                'message' => '该观测日志不存在于此世界线'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id');
        $existingLike = $post->likes()->where('user_id', $userId)->first();

        if ($existingLike) {
            // 取消点赞
            $existingLike->delete();
            $post->decrement('like_count');
            $isLiked = false;
            $message = '已取消赞同';
        } else {
            // 点赞
            $post->likes()->create(['user_id' => $userId]);
            $post->increment('like_count');
            $isLiked = true;
            $message = '已赞同';
        }

        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => [
                'is_liked' => $isLiked,
                'like_count' => $post->fresh()->like_count
            ]
        ]);
    }
}
