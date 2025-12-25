<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 发起方用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 好友用户
     */
    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }

    /**
     * 检查两个用户是否为好友
     */
    public static function areFriends(int $userId1, int $userId2): bool
    {
        return self::where('user_id', $userId1)
            ->where('friend_id', $userId2)
            ->where('status', 'accepted')
            ->exists();
    }

    /**
     * 检查是否存在待处理的好友请求
     */
    public static function hasPendingRequest(int $fromUserId, int $toUserId): bool
    {
        return self::where('user_id', $fromUserId)
            ->where('friend_id', $toUserId)
            ->where('status', 'pending')
            ->exists();
    }
}
