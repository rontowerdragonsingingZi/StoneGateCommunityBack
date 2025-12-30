<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtService
{
    private string $key;
    private string $algorithm = 'HS256';
    private int $ttl = 86400; // 24小时
    private int $botTtl = 86400 * 365; // 机器人 Token 1年

    public function __construct()
    {
        $this->key = config('app.key');
    }

    /**
     * 生成 JWT Token
     */
    public function encode(array $payload): string
    {
        $now = time();
        $payload = array_merge($payload, [
            'iat' => $now,           // 签发时间
            'exp' => $now + $this->ttl, // 过期时间
            'iss' => 'Future Gadget Lab', // 签发者
        ]);

        return JWT::encode($payload, $this->key, $this->algorithm);
    }

    /**
     * 为机器人生成长期 Token
     */
    public function encodeBot(int $userId, string $userName): string
    {
        $now = time();
        $payload = [
            'user_id' => $userId,
            'user_name' => $userName,
            'is_bot' => true,
            'iat' => $now,
            'exp' => $now + $this->botTtl,
            'iss' => 'Future Gadget Lab Bot',
        ];

        return JWT::encode($payload, $this->key, $this->algorithm);
    }

    /**
     * 解码并验证 JWT Token
     * @return array|null 成功返回 payload，失败返回 null
     */
    public function decode(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->key, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            return null; // Token 过期
        } catch (\Exception $e) {
            return null; // 其他错误（签名无效等）
        }
    }

    /**
     * 从请求头获取 Token
     */
    public static function getTokenFromHeader(?string $authHeader): ?string
    {
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }
        return substr($authHeader, 7);
    }
}
