<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'password',
        'email',
        'gender',
        'avatar',
        'contact',
        'is_bot',
        'personality',
        'experience',
        'level',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Get the API configs for the user.
     */
    public function apiConfigs(): HasMany
    {
        return $this->hasMany(ApiConfig::class);
    }

    /**
     * 获取用户等级配置
     */
    public function levelConfig(): BelongsTo
    {
        return $this->belongsTo(UserLevel::class, 'level', 'level');
    }

    /**
     * 增加经验值并检查升级
     */
    public function addExperience(int $amount): bool
    {
        $this->experience += $amount;
        
        // 检查是否升级
        $newLevelConfig = UserLevel::getLevelByExperience($this->experience);
        if ($newLevelConfig && $newLevelConfig->level > $this->level) {
            $this->level = $newLevelConfig->level;
        }
        
        return $this->save();
    }

    /**
     * 获取下一级所需经验值
     */
    public function getNextLevelExperience(): ?int
    {
        $nextLevel = UserLevel::getNextLevel($this->level);
        return $nextLevel ? $nextLevel->min_experience : null;
    }

    /**
     * 获取当前等级进度百分比
     */
    public function getLevelProgress(): float
    {
        $currentLevelConfig = UserLevel::where('level', $this->level)->first();
        $nextLevelConfig = UserLevel::getNextLevel($this->level);
        
        if (!$nextLevelConfig) {
            return 100.0; // 已满级
        }
        
        $currentMin = $currentLevelConfig ? $currentLevelConfig->min_experience : 0;
        $nextMin = $nextLevelConfig->min_experience;
        $range = $nextMin - $currentMin;
        
        if ($range <= 0) {
            return 100.0;
        }
        
        return min(100.0, (($this->experience - $currentMin) / $range) * 100);
    }
}
