<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UploadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:500',
        ]);

        if (!config('filesystems.disks.r2.bucket')) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：R2 Bucket 未配置(R2_BUCKET)',
                'data' => null,
            ], 500);
        }
        if (!config('filesystems.disks.r2.endpoint')) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：R2 Endpoint 未配置(R2_ENDPOINT)',
                'data' => null,
            ], 500);
        }

        // 从 JWT 获取用户 ID，强制限制在用户自己的目录
        $userId = $request->attributes->get('jwt_user_id');
        $prefix = "users/{$userId}/";

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 50);

        $list = Storage::disk('r2')->files($prefix);

        // 排序：按键名升序
        sort($list, SORT_STRING);

        $total = count($list);
        $offset = max(0, ($page - 1) * $perPage);
        $slice = array_slice($list, $offset, $perPage);

        $publicBase = rtrim((string) env('R2_PUBLIC_BASE_URL', ''), '/');
        $items = array_map(function ($key) use ($publicBase) {
            $url = $publicBase !== '' ? ($publicBase . '/' . ltrim($key, '/')) : null;
            return [
                'key' => $key,
                'url' => $url,
            ];
        }, $slice);

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'items' => $items,
            ],
        ]);
    }

    public function presign(Request $request): JsonResponse
    {
        $request->validate([
            'key' => 'required|string',
            'expires' => 'sometimes|integer|min:60|max:86400',
        ]);

        // 从 JWT 获取用户 ID，校验所有权
        $userId = $request->attributes->get('jwt_user_id');
        $key = $request->input('key');
        $expectedPrefix = "users/{$userId}/";

        // 校验 Key 是否属于当前用户
        if (!str_starts_with($key, $expectedPrefix)) {
            return response()->json([
                'code' => 403,
                'message' => '无权访问该资源，世界线干涉被拒绝',
                'data' => null,
            ], 403);
        }

        $minutes = ceil(((int) $request->input('expires', 900)) / 60); // 默认15分钟
        try {
            $url = Storage::disk('r2')->temporaryUrl(
                $key,
                now()->addMinutes($minutes)
            );
            return response()->json([
                'code' => 200,
                'message' => 'El Psy Kongroo',
                'data' => ['url' => $url],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：无法生成临时链接',
                'data' => ['error' => $e->getMessage()],
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        // 基础校验（移除 folder 参数，路径由后端生成）
        $request->validate([
            'file' => 'required|file|image|mimes:jpg,jpeg,png,gif,webp,avif|max:51200', // 50MB
        ]);

        // 必要配置检查
        if (!config('filesystems.disks.r2.bucket')) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：R2 Bucket 未配置(R2_BUCKET)',
                'data' => null,
            ], 500);
        }
        if (!config('filesystems.disks.r2.endpoint')) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：R2 Endpoint 未配置(R2_ENDPOINT)',
                'data' => null,
            ], 500);
        }

        // 从 JWT 获取用户 ID（前端无法控制）
        $userId = $request->attributes->get('jwt_user_id');

        $file = $request->file('file');

        // 后端生成 Key：users/{user_id}/{uuid}.{ext}
        $ext = strtolower($file->getClientOriginalExtension()) ?: $file->guessExtension();
        $key = "users/{$userId}/" . Str::uuid()->toString() . ".{$ext}";

        // 上传到 R2（S3 兼容）
        $stream = fopen($file->getRealPath(), 'r');
        $ok = Storage::disk('r2')->put($key, $stream, [
            'visibility' => null,
            'ContentType' => $file->getMimeType(),
        ]);
        if (is_resource($stream)) {
            fclose($stream);
        }

        if (!$ok) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：上传失败',
                'data' => null,
            ], 500);
        }

        // 返回可访问地址
        $publicBase = rtrim((string) env('R2_PUBLIC_BASE_URL', ''), '/');
        $url = $publicBase !== '' ? ($publicBase . '/' . $key) : null;

        return response()->json([
            'code' => 201,
            'message' => '金属乌帕已穿越至R2世界线',
            'data' => [
                'key' => $key,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'url' => $url,
            ],
        ], 201);
    }

    /**
     * 通用文件上传（支持所有文件类型）
     * 用于聊天发送文件、附件等场景
     */
    public function storeFile(Request $request): JsonResponse
    {
        // 基础校验（不限制文件类型，只限制大小）
        $request->validate([
            'file' => 'required|file|max:102400', // 100MB
        ]);

        // 必要配置检查
        if (!config('filesystems.disks.r2.bucket')) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：R2 Bucket 未配置(R2_BUCKET)',
                'data' => null,
            ], 500);
        }
        if (!config('filesystems.disks.r2.endpoint')) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：R2 Endpoint 未配置(R2_ENDPOINT)',
                'data' => null,
            ], 500);
        }

        // 从 JWT 获取用户 ID
        $userId = $request->attributes->get('jwt_user_id');

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        // 后端生成 Key：users/{user_id}/files/{uuid}.{ext}
        $ext = strtolower($file->getClientOriginalExtension()) ?: $file->guessExtension() ?: 'bin';
        $key = "users/{$userId}/files/" . Str::uuid()->toString() . ".{$ext}";

        // 上传到 R2（S3 兼容）
        $stream = fopen($file->getRealPath(), 'r');
        $ok = Storage::disk('r2')->put($key, $stream, [
            'visibility' => null,
            'ContentType' => $file->getMimeType(),
        ]);
        if (is_resource($stream)) {
            fclose($stream);
        }

        if (!$ok) {
            return response()->json([
                'code' => 500,
                'message' => '世界线异常：上传失败',
                'data' => null,
            ], 500);
        }

        // 返回可访问地址
        $publicBase = rtrim((string) env('R2_PUBLIC_BASE_URL', ''), '/');
        $url = $publicBase !== '' ? ($publicBase . '/' . $key) : null;

        return response()->json([
            'code' => 201,
            'message' => '文件已传输至R2世界线',
            'data' => [
                'key' => $key,
                'name' => $originalName,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'url' => $url,
            ],
        ], 201);
    }
}
