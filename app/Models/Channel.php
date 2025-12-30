<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'announcement',
        'creator_id',
        'is_default',
        'is_private',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_private' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 频道创建者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * 频道内的消息
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'channel', 'name');
    }
}
