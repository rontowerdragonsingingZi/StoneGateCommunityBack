<?php

namespace App\Services;

use App\Models\User;

class ExperienceService
{
    /**
     * 经验值配置
     * 可以后续迁移到数据库或配置文件
     */
    const EXP_POST_CREATE = 10;      // 发帖
    const EXP_COMMENT_CREATE = 3;    // 发表评论
    const EXP_LIKE_GIVE = 1;         // 点赞他人
    const EXP_LIKE_RECEIVE = 2;      // 被点赞
    const EXP_COMMENT_RECEIVE = 1;   // 被评论
    const EXP_SHARE_CREATE = 5;      // 分享帖子
    const EXP_LOGIN_DAILY = 5;       // 每日登录

    /**
     * 发帖获得经验
     */
    public static function onPostCreated(User $user): void
    {
        $user->addExperience(self::EXP_POST_CREATE);
    }

    /**
     * 评论获得经验
     */
    public static function onCommentCreated(User $commenter, ?User $postAuthor = null): void
    {
        // 评论者获得经验
        $commenter->addExperience(self::EXP_COMMENT_CREATE);
        
        // 帖子作者获得经验（被评论）
        if ($postAuthor && $postAuthor->id !== $commenter->id) {
            $postAuthor->addExperience(self::EXP_COMMENT_RECEIVE);
        }
    }

    /**
     * 点赞获得经验
     */
    public static function onLikeCreated(User $liker, ?User $contentAuthor = null): void
    {
        // 点赞者获得经验
        $liker->addExperience(self::EXP_LIKE_GIVE);
        
        // 内容作者获得经验（被点赞）
        if ($contentAuthor && $contentAuthor->id !== $liker->id) {
            $contentAuthor->addExperience(self::EXP_LIKE_RECEIVE);
        }
    }

    /**
     * 分享获得经验
     */
    public static function onShareCreated(User $user): void
    {
        $user->addExperience(self::EXP_SHARE_CREATE);
    }

    /**
     * 每日登录获得经验
     */
    public static function onDailyLogin(User $user): void
    {
        $user->addExperience(self::EXP_LOGIN_DAILY);
    }
}
