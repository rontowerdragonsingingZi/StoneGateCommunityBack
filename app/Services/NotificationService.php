<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Post;
use App\Models\Comment;
use App\Models\PrivateMessage;
use App\Models\Friendship;
use Illuminate\Database\Eloquent\Model;

class NotificationService
{
    /**
     * 帖子被点赞
     */
    public function notifyPostLike(Post $post, int $senderId): ?Notification
    {
        // 不通知自己
        if ($post->user_id === $senderId) {
            return null;
        }

        return $this->createNotification(
            userId: $post->user_id,
            senderId: $senderId,
            type: Notification::TYPE_POST_LIKE,
            notifiable: $post,
            data: [
                'post_title' => mb_substr($post->title, 0, 50),
            ]
        );
    }

    /**
     * 评论被点赞
     */
    public function notifyCommentLike(Comment $comment, int $senderId): ?Notification
    {
        if ($comment->user_id === $senderId) {
            return null;
        }

        return $this->createNotification(
            userId: $comment->user_id,
            senderId: $senderId,
            type: Notification::TYPE_COMMENT_LIKE,
            notifiable: $comment,
            data: [
                'comment_preview' => mb_substr($comment->content, 0, 100),
                'post_id' => $comment->post_id,
            ]
        );
    }

    /**
     * 帖子收到评论
     */
    public function notifyComment(Comment $comment, Post $post, int $senderId): ?Notification
    {
        if ($post->user_id === $senderId) {
            return null;
        }

        return $this->createNotification(
            userId: $post->user_id,
            senderId: $senderId,
            type: Notification::TYPE_COMMENT,
            notifiable: $comment,
            data: [
                'post_id' => $post->id,
                'post_title' => mb_substr($post->title, 0, 50),
                'comment_preview' => mb_substr($comment->content, 0, 100),
            ]
        );
    }

    /**
     * 评论被回复
     */
    public function notifyCommentReply(Comment $reply, Comment $parentComment, int $senderId): ?Notification
    {
        // 通知被回复的用户（reply_to_user_id 或 parent 评论的作者）
        $targetUserId = $reply->reply_to_user_id ?? $parentComment->user_id;
        
        if ($targetUserId === $senderId) {
            return null;
        }

        return $this->createNotification(
            userId: $targetUserId,
            senderId: $senderId,
            type: Notification::TYPE_COMMENT_REPLY,
            notifiable: $reply,
            data: [
                'post_id' => $reply->post_id,
                'comment_preview' => mb_substr($reply->content, 0, 100),
                'parent_comment_preview' => mb_substr($parentComment->content, 0, 50),
            ]
        );
    }

    /**
     * 帖子被转发
     */
    public function notifyPostShare(Post $post, int $senderId, ?string $message = null): ?Notification
    {
        if ($post->user_id === $senderId) {
            return null;
        }

        return $this->createNotification(
            userId: $post->user_id,
            senderId: $senderId,
            type: Notification::TYPE_POST_SHARE,
            notifiable: $post,
            data: [
                'post_title' => mb_substr($post->title, 0, 50),
                'message' => $message ? mb_substr($message, 0, 100) : null,
            ]
        );
    }

    /**
     * 收到私聊消息
     */
    public function notifyPrivateMessage(PrivateMessage $message): ?Notification
    {
        if ($message->receiver_id === $message->sender_id) {
            return null;
        }

        $contentPreview = $message->type === 'text' 
            ? mb_substr($message->content, 0, 100)
            : '[' . $this->getMessageTypeLabel($message->type) . ']';

        return $this->createNotification(
            userId: $message->receiver_id,
            senderId: $message->sender_id,
            type: Notification::TYPE_PRIVATE_MESSAGE,
            notifiable: $message,
            data: [
                'message_preview' => $contentPreview,
                'message_type' => $message->type,
            ]
        );
    }

    /**
     * 收到好友请求
     */
    public function notifyFriendRequest(Friendship $friendship, int $senderId, int $receiverId): ?Notification
    {
        return $this->createNotification(
            userId: $receiverId,
            senderId: $senderId,
            type: Notification::TYPE_FRIEND_REQUEST,
            notifiable: $friendship,
            data: []
        );
    }

    /**
     * 创建通知
     */
    protected function createNotification(
        int $userId,
        int $senderId,
        string $type,
        ?Model $notifiable = null,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'sender_id' => $senderId,
            'type' => $type,
            'notifiable_id' => $notifiable?->id,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'data' => $data,
        ]);
    }

    /**
     * 获取消息类型标签
     */
    protected function getMessageTypeLabel(string $type): string
    {
        return match ($type) {
            'image' => '图片',
            'file' => '文件',
            'sticker' => '表情',
            'post_share' => '帖子分享',
            default => '消息',
        };
    }
}
