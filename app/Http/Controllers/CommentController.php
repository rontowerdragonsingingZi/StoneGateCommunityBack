<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CommentController extends Controller
{
    /**
     * 获取帖子的评论列表
     */
    public function index(Request $request, int $postId): JsonResponse
    {
        $post = Post::find($postId);
        if (!$post) {
            return response()->json([
                'code' => 404,
                'message' => '该观测日志不存在于此世界线'
            ], 404);
        }

        $limit = min($request->get('limit', 20), 50);
        
        // 获取顶级评论（parent_id 为 null）
        // 回复按时间正序排列，加载 replyToUser
        $comments = Comment::with([
                'user:id,name,avatar', 
                'replies.user:id,name,avatar',
                'replies.replyToUser:id,name'
            ])
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        // 添加当前用户是否已点赞
        $userId = $request->attributes->get('jwt_user_id');
        $currentUser = $userId ? User::find($userId) : null;
        
        $comments->getCollection()->transform(function ($comment) use ($currentUser) {
            $comment->is_liked = $this->isLikedByUser($comment, $currentUser);
            // 处理回复（已按时间正序排列）
            $comment->replies->transform(function ($reply) use ($currentUser) {
                $reply->is_liked = $this->isLikedByUser($reply, $currentUser);
                return $reply;
            });
            return $comment;
        });

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => [
                'items' => $comments->items(),
                'total' => $comments->total(),
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
            ]
        ]);
    }

    /**
     * 创建评论
     */
    public function store(Request $request, int $postId): JsonResponse
    {
        $post = Post::find($postId);
        if (!$post) {
            return response()->json([
                'code' => 404,
                'message' => '该观测日志不存在于此世界线'
            ], 404);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $actualParentId = null;
        $replyToUserId = null;

        // 处理回复逻辑：扁平化结构，所有回复都挂在主评论下
        if (!empty($validated['parent_id'])) {
            $targetComment = Comment::find($validated['parent_id']);
            if (!$targetComment || $targetComment->post_id !== $postId) {
                return response()->json([
                    'code' => 400,
                    'message' => '回复的评论不存在'
                ], 400);
            }
            
            // 如果目标是主评论（parent_id 为 null），直接作为其回复
            // 如果目标是某个回复，则 parent_id 改为其 parent_id（主评论），同时记录 reply_to_user_id
            if ($targetComment->parent_id === null) {
                // 回复主评论
                $actualParentId = $targetComment->id;
                $replyToUserId = null; // 回复主评论不需要显示 @用户
            } else {
                // 回复某个回复 -> 扁平化到主评论下
                $actualParentId = $targetComment->parent_id;
                $replyToUserId = $targetComment->user_id; // 记录被回复的用户
            }
        }
        
        $comment = Comment::create([
            'user_id' => $userId,
            'post_id' => $postId,
            'parent_id' => $actualParentId,
            'reply_to_user_id' => $replyToUserId,
            'content' => $validated['content'],
        ]);

        // 更新帖子评论数
        $post->increment('comment_count');

        $comment->load(['user:id,name,avatar', 'replyToUser:id,name']);

        return response()->json([
            'code' => 201,
            'message' => '评论已发送至世界线',
            'data' => $comment
        ], 201);
    }

    /**
     * 删除评论
     */
    public function destroy(Request $request, int $postId, int $commentId): JsonResponse
    {
        $comment = Comment::where('post_id', $postId)->find($commentId);
        if (!$comment) {
            return response()->json([
                'code' => 404,
                'message' => '评论不存在'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id');
        if ($comment->user_id !== $userId) {
            return response()->json([
                'code' => 403,
                'message' => '无权删除他人的评论'
            ], 403);
        }

        // 计算要减少的评论数（包括回复）
        $replyCount = $comment->replies()->count();
        
        $comment->delete();

        // 更新帖子评论数
        $post = Post::find($postId);
        if ($post) {
            $post->decrement('comment_count', 1 + $replyCount);
        }

        return response()->json([
            'code' => 200,
            'message' => '评论已从世界线中移除'
        ]);
    }

    /**
     * 点赞/取消点赞评论
     */
    public function toggleLike(Request $request, int $postId, int $commentId): JsonResponse
    {
        $comment = Comment::where('post_id', $postId)->find($commentId);
        if (!$comment) {
            return response()->json([
                'code' => 404,
                'message' => '评论不存在'
            ], 404);
        }

        $userId = $request->attributes->get('jwt_user_id');
        $existingLike = $comment->likes()->where('user_id', $userId)->first();

        if ($existingLike) {
            $existingLike->delete();
            $comment->decrement('like_count');
            $isLiked = false;
            $message = '已取消赞同';
        } else {
            $comment->likes()->create(['user_id' => $userId]);
            $comment->increment('like_count');
            $isLiked = true;
            $message = '已赞同';
        }

        return response()->json([
            'code' => 200,
            'message' => $message,
            'data' => [
                'is_liked' => $isLiked,
                'like_count' => $comment->fresh()->like_count
            ]
        ]);
    }

    /**
     * 检查用户是否已点赞
     */
    private function isLikedByUser(Comment $comment, ?User $user): bool
    {
        if (!$user) return false;
        return $comment->likes()->where('user_id', $user->id)->exists();
    }
}
