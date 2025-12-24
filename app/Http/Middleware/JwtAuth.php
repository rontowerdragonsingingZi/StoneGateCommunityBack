<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;

class JwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        $token = JwtService::getTokenFromHeader($authHeader);

        if (!$token) {
            return response()->json([
                'code' => 401,
                'message' => '未提供认证令牌，请先登录Future Gadget Lab',
                'data' => null,
            ], 401);
        }

        $jwt = new JwtService();
        $payload = $jwt->decode($token);

        if (!$payload) {
            return response()->json([
                'code' => 401,
                'message' => '认证令牌已失效，请重新登录',
                'data' => null,
            ], 401);
        }

        // 将用户信息附加到请求中
        $request->attributes->set('jwt_user_id', $payload['user_id'] ?? null);
        $request->attributes->set('jwt_user_name', $payload['user_name'] ?? null);

        return $next($request);
    }
}
