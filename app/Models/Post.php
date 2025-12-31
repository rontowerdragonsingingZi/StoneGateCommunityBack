<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'cover',
        'tag',
    ];

    protected $casts = [
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'share_count' => 'integer',
    ];

    // 作者
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 点赞（多态）
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // 评论
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // 转发
    public function shares(): HasMany
    {
        return $this->hasMany(Share::class);
    }

    // 检查用户是否已点赞
    public function isLikedBy(?User $user): bool
    {
        if (!$user) return false;
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    // 增加浏览量
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
