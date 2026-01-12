<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLevel extends Model
{
    protected $fillable = [
        'level',
        'title',
        'code',
        'icon',
        'quote',
        'description',
        'min_experience',
        'color',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_experience' => 'integer',
    ];

    /**
     * 根据经验值获取对应的等级配置
     */
    public static function getLevelByExperience(int $experience): ?self
    {
        return self::where('min_experience', '<=', $experience)
            ->orderBy('min_experience', 'desc')
            ->first();
    }

    /**
     * 获取下一等级配置
     */
    public static function getNextLevel(int $currentLevel): ?self
    {
        return self::where('level', '>', $currentLevel)
            ->orderBy('level', 'asc')
            ->first();
    }
}
