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
            'folder' => 'sometimes|string|max:128',
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:500',
            'deep' => 'sometimes|boolean',
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

        $folder = trim($request->input('folder', ''), '/');
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 50);
        $deep = (bool) $request->boolean('deep', true);

        $list = $deep
            ? \Illuminate\Support\Facades\Storage::disk('r2')->allFiles($folder)
            : \Illuminate\Support\Facades\Storage::disk('r2')->files($folder);

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
        $minutes = ceil(((int) $request->input('expires', 900)) / 60); // 默认15分钟
        try {
            $url = \Illuminate\Support\Facades\Storage::disk('r2')->temporaryUrl(
                $request->string('key'),
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
        // 基础校验
        $request->validate([
'file' => 'required|file|image|mimes:jpg,jpeg,png,gif,webp,avif|max:51200', // 50MB
            'folder' => 'sometimes|string|max:128'
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

        $file = $request->file('file');
        $folder = trim($request->input('folder', 'uploads/images'), '/');

        $datePath = now()->format('Y/m/d');
        $ext = strtolower($file->getClientOriginalExtension());
        $basename = Str::uuid()->toString();
        $filename = $basename . '.' . $ext;
        $key = $folder . '/' . $datePath . '/' . $filename;

        // 上传到 R2（S3 兼容）
        // 注意：R2 建议使用 path-style endpoint，配置已在 filesystems.php 设置
        $stream = fopen($file->getRealPath(), 'r');
        $ok = Storage::disk('r2')->put($key, $stream, [
            // R2 多数情况下不支持对象级 ACL，公有访问请在桶策略或自定义域开启
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

        // 返回可访问地址（若提供 R2_PUBLIC_BASE_URL 则拼接公开地址）
        $publicBase = rtrim((string) env('R2_PUBLIC_BASE_URL', ''), '/');
        $url = $publicBase !== '' ? ($publicBase . '/' . $key) : null;

        return response()->json([
            'code' => 201,
            'message' => '金属乌帕已穿越至R2世界线',
            'data' => [
                'key' => $key,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'url' => $url, // 若桶未公开，此处可能为 null，可在前端改用你配置的 CDN 域名
            ],
        ], 201);
    }
}
