<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\User;
use App\Services\BotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 异步处理机器人回复
 * 当有新消息时，检查是否需要机器人回复
 */
class ProcessBotReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 任务最大尝试次数
     */
    public int $tries = 3;

    /**
     * 任务超时时间（秒）
     */
    public int $timeout = 60;

    /**
     * 要处理的消息
     */
    private Message $message;

    /**
     * 创建任务实例
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
        $this->onQueue('bot-replies'); // 使用专门的队列
    }

    /**
     * 执行任务
     */
    public function handle(BotService $botService): void
    {
        // 重新加载消息（确保数据最新）
        $this->message->refresh();

        // 获取所有机器人配置
        $botConfigs = $botService->getAllBotConfigs();

        if ($botConfigs->isEmpty()) {
            Log::debug('No bot configs found');
            return;
        }

        foreach ($botConfigs as $config) {
            $bot = $config->user;

            // 检查是否应该回复
            if (!$botService->shouldReply($this->message, $bot)) {
                continue;
            }

            // 模拟"正在输入"延迟（1-3秒）
            $typingDelay = rand(1000, 3000);
            usleep($typingDelay * 1000);

            // 生成回复
            $reply = $botService->generateReply($this->message, $bot, $config->api_key);

            if ($reply) {
                // 模拟打字时间（根据回复长度）
                $typingTime = $this->calculateTypingTime($reply);
                usleep($typingTime * 1000);

                // 发送消息
                $botService->sendMessage($bot, $this->message->channel, $reply);

                Log::info('Bot replied', [
                    'bot' => $bot->name,
                    'channel' => $this->message->channel,
                    'reply_length' => mb_strlen($reply),
                ]);

                // 一条消息只让一个机器人回复（大部分情况）
                // 小概率多个机器人回复（增加趣味）
                if (rand(1, 100) > 10) {
                    break;
                }
            }
        }
    }

    /**
     * 计算模拟打字时间（毫秒）
     * 模拟每秒打 5-8 个字
     */
    private function calculateTypingTime(string $content): int
    {
        $length = mb_strlen($content);
        $charsPerSecond = rand(5, 8);
        $baseTime = ($length / $charsPerSecond) * 1000;

        // 添加一些随机波动
        $variation = $baseTime * 0.2 * (rand(0, 100) - 50) / 100;

        return (int) max(500, min($baseTime + $variation, 5000)); // 500ms - 5s
    }

    /**
     * 任务失败处理
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessBotReplyJob failed', [
            'message_id' => $this->message->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
