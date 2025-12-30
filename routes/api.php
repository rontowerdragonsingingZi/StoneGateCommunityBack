<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\PrivateChatController;
use App\Http\Controllers\StickerController;
use App\Http\Controllers\BotController;
use App\Http\Middleware\JwtAuth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 不需要JWT鉴权的路由
Route::prefix('users')->group(function () {
    Route::post('/', [UserController::class, 'store']);          // 注册新Labmem
    Route::post('/login', [UserController::class, 'login']);     // Labmem登录
    Route::post('/check-username', [UserController::class, 'checkUsername']); // 检查代号是否可用
});

// 需要JWT鉴权的路由
Route::middleware(JwtAuth::class)->group(function () {
    // 用户相关
    Route::prefix('users')->group(function () {
        Route::get('/search', [FriendController::class, 'searchUsers']); // 搜索用户（用于添加好友）
        Route::get('/', [UserController::class, 'index']);           // 获取所有Labmem
        Route::get('/{id}', [UserController::class, 'show']);        // 获取单个Labmem信息
        Route::put('/{id}', [UserController::class, 'update']);      // 更新Labmem信息
        Route::delete('/{id}', [UserController::class, 'destroy']);  // 移除Labmem
    });

    // 图床相关
    Route::post('/upload-image', [UploadController::class, 'store']);     // 图片上传
    Route::post('/upload-file', [UploadController::class, 'storeFile']);  // 通用文件上传
    Route::get('/images', [UploadController::class, 'index']);            // 列出图片
    Route::get('/images/presign', [UploadController::class, 'presign']);  // 生成临时访问链接

    // 广播频道认证（WebSocket）
    Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate']);

    // 聊天相关
    Route::prefix('chat')->group(function () {
        Route::post('/send', [ChatController::class, 'send']);       // 发送消息
        Route::get('/history', [ChatController::class, 'history']);  // 获取历史消息
    });

    // 频道相关
    Route::prefix('channels')->group(function () {
        Route::get('/', [ChannelController::class, 'index']);        // 获取频道列表
        Route::get('/mine', [ChannelController::class, 'mine']);     // 获取我创建的频道
        Route::post('/', [ChannelController::class, 'store']);       // 创建新频道
        Route::get('/{name}', [ChannelController::class, 'show']);   // 获取频道详情
    });

    // 好友相关
    Route::prefix('friends')->group(function () {
        Route::get('/', [FriendController::class, 'index']);              // 获取好友列表
        Route::post('/request', [FriendController::class, 'sendRequest']); // 发送好友请求
        Route::get('/requests', [FriendController::class, 'getRequests']); // 获取待处理请求
        Route::post('/{id}/accept', [FriendController::class, 'acceptRequest']); // 接受请求
        Route::post('/{id}/reject', [FriendController::class, 'rejectRequest']); // 拒绝请求
        Route::delete('/{friendId}', [FriendController::class, 'destroy']); // 删除好友
    });

    // 私聊相关
    Route::prefix('private-chat')->group(function () {
        Route::post('/send', [PrivateChatController::class, 'send']);       // 发送私聊消息
        Route::get('/history', [PrivateChatController::class, 'history']);  // 获取私聊历史
    });

    // 表情相关
    Route::prefix('stickers')->group(function () {
        Route::get('/', [StickerController::class, 'index']);              // 获取我的表情列表
        Route::get('/system', [StickerController::class, 'system']);       // 获取系统表情
        Route::get('/public', [StickerController::class, 'public']);       // 获取公开表情
        Route::post('/', [StickerController::class, 'store']);             // 上传新表情
        Route::post('/default', [StickerController::class, 'storeDefault']); // 管理员上传默认表情
        Route::post('/{id}/collect', [StickerController::class, 'collect']);     // 收藏表情
        Route::delete('/{id}/collect', [StickerController::class, 'uncollect']); // 取消收藏
        Route::delete('/{id}', [StickerController::class, 'destroy']);     // 删除自己的表情
    });

    // 机器人相关（内部API）
    Route::prefix('bot')->group(function () {
        Route::get('/configs', [BotController::class, 'configs']);         // 获取所有API配置
    });
});
