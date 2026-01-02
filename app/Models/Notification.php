<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    // 通知类型常量
    const TYPE_POST_LIKE = 'post_like';
    const TYPE_COMMENT_LIKE = 'comment_like';
    const TYPE_COMMENT = 'comment';
    const TYPE_COMMENT_REPLY = 'comment_reply';
    const TYPE_POST_SHARE = 'post_share';
    const TYPE_PRIVATE_MESSAGE = 'private_message';
    const TYPE_FRIEND_REQUEST = 'friend_request';

    protected $fillable = [
        'user_id',
        'sender_id',
        'type',
        'notifiable_id',
        'notifiable_type',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    /**
     * 接收通知的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 发送通知的用户（触发者）
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * 多态关联的对象
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 标记为已读
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * 是否已读
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * 查询范围：未读通知
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * 查询范围：按类型筛选
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
