<?php

namespace App\Http\Controllers;

use App\Models\Sticker;
use App\Models\User;
use App\Models\UserStickerCollection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StickerController extends Controller
{
    /**
     * 获取表情列表（默认表情 + 我的表情）
     * 我的表情 = 用户上传的 + 收藏的
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');
        $category = $request->query('category');

        // 默认表情（系统表情，user_id 为 null）
        $defaultQuery = Sticker::whereNull('user_id');
        if ($category) {
            $defaultQuery->where('category', $category);
        }
        $defaultStickers = $defaultQuery->orderBy('category')->orderBy('id')->get();

        // 用户上传的表情
        $myQuery = Sticker::where('user_id', $userId);
        if ($category) {
            $myQuery->where('category', $category);
        }
        $myStickers = $myQuery->orderByDesc('created_at')->get()
            ->map(function ($sticker) {
                $sticker->is_collected = false;
                $sticker->is_own = true;
                return $sticker;
            });

        // 收藏的表情
        $collectedIds = UserStickerCollection::where('user_id', $userId)
            ->pluck('sticker_id');
        $collectedQuery = Sticker::whereIn('id', $collectedIds);
        if ($category) {
            $collectedQuery->where('category', $category);
        }
        $collectedStickers = $collectedQuery->orderBy('created_at', 'desc')->get()
            ->map(function ($sticker) {
                $sticker->is_collected = true;
                $sticker->is_own = false;
                return $sticker;
            });

        // 合并我的表情：用户上传的 + 收藏的
        $mineStickers = $myStickers->concat($collectedStickers)->values();

        return response()->json([
            'code' => 200,
            'data' => [
                'default' => $defaultStickers,
                'mine' => $mineStickers,
            ]
        ]);
    }

    /**
     * 获取系统表情（用于展示所有可用表情）
     */
    public function system(Request $request): JsonResponse
    {
        $category = $request->query('category');

        $query = Sticker::whereNull('user_id');
        if ($category) {
            $query->where('category', $category);
        }

        $stickers = $query->orderBy('category')->orderBy('id')->get();

        // 按分类分组
        $grouped = $stickers->groupBy('category');

        return response()->json([
            'code' => 200,
            'data' => $grouped
        ]);
    }

    /**
     * 获取公开表情（其他用户上传的公开表情）
     */
    public function public(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');
        $page = $request->query('page', 1);
        $perPage = $request->query('per_page', 50);

        $stickers = Sticker::whereNotNull('user_id')
            ->where('user_id', '!=', $userId)
            ->where('is_public', true)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'code' => 200,
            'data' => $stickers->items(),
            'meta' => [
                'current_page' => $stickers->currentPage(),
                'last_page' => $stickers->lastPage(),
                'total' => $stickers->total(),
            ]
        ]);
    }

    /**
     * 上传新表情（普通用户上传）
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:2048', // 最大2MB
            'name' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:50',
            'is_public' => 'nullable|boolean',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        $file = $request->file('file');

        // 生成唯一文件名
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $filename = Str::uuid() . '.' . $ext;
        $path = "users/{$userId}/stickers/{$filename}";

        // 上传到 R2
        Storage::disk('r2')->put($path, file_get_contents($file), 'public');

        // 生成公开 URL
        $url = config('filesystems.disks.r2.public_url') . '/' . $path;

        // 保存到数据库
        $sticker = Sticker::create([
            'user_id' => $userId,
            'name' => $request->input('name', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
            'url' => $url,
            'category' => $request->input('category', 'custom'),
            'is_public' => $request->input('is_public', true),
        ]);

        return response()->json([
            'code' => 201,
            'message' => '表情上传成功',
            'data' => $sticker
        ], 201);
    }

    /**
     * 管理员上传默认表情
     * 只有用户名为 admin 的用户才能调用
     */
    public function storeDefault(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|max:2048', // 最大2MB
            'name' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:50',
        ]);

        $userId = $request->attributes->get('jwt_user_id');
        
        // 检查是否为管理员
        $user = User::find($userId);
        if (!$user || $user->name !== 'admin') {
            return response()->json([
                'code' => 403,
                'message' => '无权限，只有管理员才能上传默认表情'
            ], 403);
        }

        $file = $request->file('file');

        // 生成唯一文件名
        $ext = $file->getClientOriginalExtension() ?: 'png';
        $filename = Str::uuid() . '.' . $ext;
        $path = "system/stickers/{$filename}";

        // 上传到 R2
        Storage::disk('r2')->put($path, file_get_contents($file), 'public');

        // 生成公开 URL
        $url = config('filesystems.disks.r2.public_url') . '/' . $path;

        // 保存到数据库，user_id 为 null 表示默认表情
        $sticker = Sticker::create([
            'user_id' => null,
            'name' => $request->input('name', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
            'url' => $url,
            'category' => $request->input('category', 'default'),
            'is_public' => true,
        ]);

        return response()->json([
            'code' => 201,
            'message' => '默认表情上传成功',
            'data' => $sticker
        ], 201);
    }

    /**
     * 收藏表情
     */
    public function collect(Request $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        // 检查表情是否存在
        $sticker = Sticker::find($id);
        if (!$sticker) {
            return response()->json([
                'code' => 404,
                'message' => '表情不存在'
            ], 404);
        }

        // 不能收藏自己的表情
        if ($sticker->user_id === $userId) {
            return response()->json([
                'code' => 400,
                'message' => '不能收藏自己的表情'
            ], 400);
        }

        // 检查是否已收藏
        $exists = UserStickerCollection::where('user_id', $userId)
            ->where('sticker_id', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'code' => 400,
                'message' => '已经收藏过了'
            ], 400);
        }

        UserStickerCollection::create([
            'user_id' => $userId,
            'sticker_id' => $id,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '收藏成功'
        ]);
    }

    /**
     * 取消收藏
     */
    public function uncollect(Request $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $deleted = UserStickerCollection::where('user_id', $userId)
            ->where('sticker_id', $id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'code' => 404,
                'message' => '未找到收藏记录'
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => '取消收藏成功'
        ]);
    }

    /**
     * 删除自己的表情
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $userId = $request->attributes->get('jwt_user_id');

        $sticker = Sticker::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$sticker) {
            return response()->json([
                'code' => 404,
                'message' => '表情不存在或无权删除'
            ], 404);
        }

        // 删除 R2 上的文件
        $path = str_replace(config('filesystems.disks.r2.public_url') . '/', '', $sticker->url);
        Storage::disk('r2')->delete($path);

        // 删除数据库记录（关联的收藏记录会级联删除）
        $sticker->delete();

        return response()->json([
            'code' => 200,
            'message' => '删除成功'
        ]);
    }
}
