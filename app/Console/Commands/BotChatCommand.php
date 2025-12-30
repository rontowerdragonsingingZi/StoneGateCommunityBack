<?php

namespace App\Console\Commands;

use App\Services\BotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 机器人主动聊天命令
 * 可通过定时任务调用，让机器人主动在频道发言
 */
class BotChatCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'bot:chat
                            {--channel=lobby : 目标频道}
                            {--probability=30 : 发言概率(1-100)}';

    /**
     * 命令描述
     */
    protected $description = '让机器人在指定频道主动发言';

    /**
     * 执行命令
     */
    public function handle(BotService $botService): int
    {
        $channel = $this->option('channel');
        $probability = (int) $this->option('probability');

        // 概率检查
        if (rand(1, 100) > $probability) {
            $this->info("随机概率未命中，跳过发言");
            return self::SUCCESS;
        }

        $this->info("尝试在频道 [{$channel}] 发起聊天...");

        $result = $botService->initiateChat($channel);

        if ($result) {
            $bot = $result['bot'];
            $content = $result['content'];

            // 发送消息
            $message = $botService->sendMessage($bot, $channel, $content);

            $this->info("✓ {$bot->name} 发言: " . mb_substr($content, 0, 50) . '...');

            Log::info('Bot initiated chat', [
                'bot' => $bot->name,
                'channel' => $channel,
                'message_id' => $message->id,
            ]);

            return self::SUCCESS;
        }

        $this->warn("没有机器人发言（可能在冷却中或没有配置）");
        return self::SUCCESS;
    }
}
