<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * DeepSeek API 服务封装
 * 提供 AI 对话能力
 */
class DeepSeekService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;
    private int $maxRetries;
    private int $timeout;

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key', '');
        $this->baseUrl = config('services.deepseek.base_url', 'https://api.deepseek.com');
        $this->model = config('services.deepseek.model', 'deepseek-chat');
        $this->maxRetries = config('services.deepseek.max_retries', 3);
        $this->timeout = config('services.deepseek.timeout', 30);
    }

    /**
     * 使用指定的 API Key 发送聊天请求
     *
     * @param string $apiKey API 密钥
     * @param array $messages 消息数组 [['role' => 'user', 'content' => '...'], ...]
     * @param string|null $systemPrompt 系统提示词（人格设定）
     * @param array $options 额外选项 (temperature, max_tokens 等)
     * @return string|null 回复内容
     */
    public function chat(string $apiKey, array $messages, ?string $systemPrompt = null, array $options = []): ?string
    {
        $fullMessages = [];

        // 添加系统提示词
        if ($systemPrompt) {
            $fullMessages[] = [
                'role' => 'system',
                'content' => $systemPrompt,
            ];
        }

        // 添加对话消息
        $fullMessages = array_merge($fullMessages, $messages);

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $fullMessages,
            'temperature' => $options['temperature'] ?? 0.8,
            'max_tokens' => $options['max_tokens'] ?? 500,
            'top_p' => $options['top_p'] ?? 0.9,
        ];

        return $this->sendRequest($apiKey, $payload);
    }

    /**
     * 发送 API 请求（带重试机制）
     */
    private function sendRequest(string $apiKey, array $payload): ?string
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->post($this->baseUrl . '/v1/chat/completions', $payload);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['choices'][0]['message']['content'] ?? null;
                }

                // 处理 API 错误
                $error = $response->json('error.message') ?? $response->body();
                Log::warning("DeepSeek API error (attempt {$attempt})", [
                    'status' => $response->status(),
                    'error' => $error,
                ]);

                // 如果是 429 (Rate Limit) 或 5xx 错误，重试
                if ($response->status() === 429 || $response->status() >= 500) {
                    $this->delay($attempt);
                    continue;
                }

                // 其他错误不重试
                return null;

            } catch (Exception $e) {
                $lastException = $e;
                Log::warning("DeepSeek API exception (attempt {$attempt})", [
                    'message' => $e->getMessage(),
                ]);
                $this->delay($attempt);
            }
        }

        Log::error('DeepSeek API failed after all retries', [
            'last_exception' => $lastException?->getMessage(),
        ]);

        return null;
    }

    /**
     * 指数退避延迟
     */
    private function delay(int $attempt): void
    {
        $delay = min(pow(2, $attempt) * 1000, 10000); // 最多 10 秒
        usleep($delay * 1000);
    }

    /**
     * 验证 API Key 是否有效
     */
    public function validateApiKey(string $apiKey): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])
            ->timeout(10)
            ->get($this->baseUrl . '/v1/models');

            return $response->successful();
        } catch (Exception $e) {
            return false;
        }
    }
}
