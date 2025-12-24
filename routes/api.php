<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UploadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 用戶 CRUD 路由
Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index']);           // 獲取所有用戶
    Route::post('/', [UserController::class, 'store']);          // 創建用戶
    Route::get('/{id}', [UserController::class, 'show']);        // 獲取單個用戶
    Route::put('/{id}', [UserController::class, 'update']);      // 更新用戶
    Route::delete('/{id}', [UserController::class, 'destroy']);  // 刪除用戶
    Route::post('/login', [UserController::class, 'login']);     // 用戶登錄
    Route::post('/check-username', [UserController::class, 'checkUsername']); // 檢查用戶名
});

// 图片上传
Route::post('/upload-image', [UploadController::class, 'store']);
// 列出图片（支持 folder、page、per_page、deep）
Route::get('/images', [UploadController::class, 'index']);
// 私有桶：生成临时访问链接
Route::get('/images/presign', [UploadController::class, 'presign']);
