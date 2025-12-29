<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStickerCollection extends Model
{
    protected $table = 'user_sticker_collections';
    
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'sticker_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * 收藏者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 被收藏的表情
     */
    public function sticker(): BelongsTo
    {
        return $this->belongsTo(Sticker::class);
    }
}
