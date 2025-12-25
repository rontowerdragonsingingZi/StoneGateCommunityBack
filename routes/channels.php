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

// 公共大厅 - Presence Channel（可获取在线用户列表）
Broadcast::channel('lobby', function ($user) {
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

// 私聊频道（扩展用）
Broadcast::channel('chat.{recipientId}', function ($user, $recipientId) {
    return $user ? ['id' => $user->id, 'name' => $user->name] : false;
});
