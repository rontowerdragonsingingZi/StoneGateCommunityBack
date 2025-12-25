<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| 大厅和私聊频道认证
|
*/

// 聊天频道 - Presence Channel（支持动态频道名，可获取在线用户列表）
Broadcast::channel('chat.{channelName}', function ($user, $channelName) {
    if ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar,
        ];
    }
    return false;
});

// 私人用户频道（扩展用）
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// 私聊频道 - Private Channel
// 频道名格式: private-chat.{minId}_{maxId}
Broadcast::channel('private-chat.{conversationId}', function ($user, $conversationId) {
    // 解析 conversationId，验证用户是否为会话参与者
    $ids = explode('_', $conversationId);
    if (count($ids) !== 2) {
        return false;
    }
    $userId1 = (int) $ids[0];
    $userId2 = (int) $ids[1];
    
    // 检查当前用户是否为会话参与者
    if ((int) $user->id === $userId1 || (int) $user->id === $userId2) {
        return true;
    }
    return false;
});
