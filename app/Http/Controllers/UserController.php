<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Services\JwtService;

class UserController extends Controller
{
    /**
     * 獲取所有用戶列表
     */
    public function index(): JsonResponse
    {
        $users = User::all();
        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => $users
        ]);
    }

    /**
     * 創建新用戶
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female,other',
            'avatar' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
        ]);

        $user = User::create($validated);

        return response()->json([
            'code' => 201,
            'message' => '欢迎加入Future Gadget Lab，Labmem注册完成',
            'data' => $user
        ], 201);
    }

    /**
     * 獲取單個用戶詳情
     */
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => '该Labmem不存在于此世界线',
                'data' => null
            ], 404);
        }

        return response()->json([
            'code' => 200,
            'message' => 'El Psy Kongroo',
            'data' => $user
        ]);
    }

    /**
     * 更新用戶信息
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => '该Labmem不存在于此世界线',
                'data' => null
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'password' => 'sometimes|required|string|min:6',
            'email' => 'nullable|email|max:255',
            'gender' => 'nullable|in:male,female,other',
            'avatar' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return response()->json([
            'code' => 200,
            'message' => 'Labmem信息已在世界线中更新',
            'data' => $user
        ]);
    }

    /**
     * 刪除用戶
     */
    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => '该Labmem不存在于此世界线',
                'data' => null
            ], 404);
        }

        $user->delete();

        return response()->json([
            'code' => 200,
            'message' => 'Labmem已从此世界线消失',
            'data' => null
        ]);
    }

    /**
     * 用戶登錄驗證
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('name', $validated['name'])->first();

        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => '该Labmem不存在于此世界线',
                'data' => null
            ], 404);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'code' => 401,
                'message' => '认证失败，D-Mail密钥不匹配',
                'data' => null
            ], 401);
        }

        // 生成JWT令牌
        $jwt = new JwtService();
        $token = $jwt->encode([
            'user_id' => $user->id,
            'user_name' => $user->name,
        ]);

        return response()->json([
            'code' => 200,
            'message' => '世界线变动率确认，欢迎回来Labmem',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }

    /**
     * 檢查用戶名是否可用
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
        ]);

        $exists = User::where('name', $validated['name'])->exists();

        if ($exists) {
            return response()->json([
                'code' => 409,
                'message' => '此世界线已存在同名Labmem，请更换代号',
                'data' => null
            ], 409);
        }

        return response()->json([
            'code' => 200,
            'message' => '该代号可用，欢迎加入Future Gadget Lab',
            'data' => null
        ]);
    }
}
