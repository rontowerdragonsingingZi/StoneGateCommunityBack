<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ChannelController;
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
        Route::get('/', [UserController::class, 'index']);           // 获取所有Labmem
        Route::get('/{id}', [UserController::class, 'show']);        // 获取单个Labmem信息
        Route::put('/{id}', [UserController::class, 'update']);      // 更新Labmem信息
        Route::delete('/{id}', [UserController::class, 'destroy']);  // 移除Labmem
    });

    // 图床相关
    Route::post('/upload-image', [UploadController::class, 'store']);     // 图片上传
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
        Route::post('/', [ChannelController::class, 'store']);       // 创建新频道
        Route::get('/{name}', [ChannelController::class, 'show']);   // 获取频道详情
    });
});
