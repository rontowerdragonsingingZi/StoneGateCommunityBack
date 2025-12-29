<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Sticker extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'category',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 表情上传者（null表示系统表情）
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 收藏此表情的用户
     */
    public function collectors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_sticker_collections')
            ->withTimestamps();
    }

    /**
     * 是否为系统表情
     */
    public function isSystem(): bool
    {
        return $this->user_id === null;
    }
}
