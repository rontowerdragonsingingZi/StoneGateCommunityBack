<?php

namespace App\Console\Commands;

use App\Services\BotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 机器人守护进程
 * 持续运行，在随机时间间隔内让机器人主动发言
 */
class BotDaemonCommand extends Command
{
    /**
     * 命令签名
     */
    protected $signature = 'bot:daemon
                            {--channel=lobby : 目标频道}
                            {--min-interval=60 : 最小发言间隔(秒)}
                            {--max-interval=300 : 最大发言间隔(秒)}
                            {--quiet-start=23 : 安静时段开始(小时,0-23)}
                            {--quiet-end=7 : 安静时段结束(小时,0-23)}';

    /**
     * 命令描述
     */
    protected $description = '启动机器人守护进程，随机时间主动发言';

    /**
     * 是否应该继续运行
     */
    private bool $shouldRun = true;

    /**
     * 执行命令
     */
    public function handle(BotService $botService): int
    {
        $channel = $this->option('channel');
        $minInterval = (int) $this->option('min-interval');
        $maxInterval = (int) $this->option('max-interval');
        $quietStart = (int) $this->option('quiet-start');
        $quietEnd = (int) $this->option('quiet-end');

        $this->info("机器人守护进程启动");
        $this->info("   频道: {$channel}");
        $this->info("   发言间隔: {$minInterval}-{$maxInterval} 秒");
        $this->info("   安静时段: {$quietStart}:00 - {$quietEnd}:00");
        $this->info("   按 Ctrl+C 停止");
        $this->newLine();

        // 注册信号处理
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, fn () => $this->shouldRun = false);
            pcntl_signal(SIGINT, fn () => $this->shouldRun = false);
        }

        while ($this->shouldRun) {
            // 检查是否在安静时段
            if ($this->isQuietHours($quietStart, $quietEnd)) {
                $this->line("[" . $this->now8() . "] 安静时段，跳过发言...");
                sleep(300); // 安静时段每5分钟检查一次
                continue;
            }

            // 尝试让机器人发言
            $result = $botService->initiateChat($channel);

            if ($result) {
                $bot = $result['bot'];
                $content = $result['content'];

                // 发送消息
                $message = $botService->sendMessage($bot, $channel, $content);

                $this->info("[" . $this->now8() . "] ✓ {$bot->name}: " . mb_substr($content, 0, 40) . '...');

                Log::info('Bot daemon: message sent', [
                    'bot' => $bot->name,
                    'channel' => $channel,
                    'message_id' => $message->id,
                ]);
            } else {
                $this->line("[" . $this->now8() . "] - 跳过（冷却中或无配置）");
            }

            // 随机等待
            $sleepTime = rand($minInterval, $maxInterval);
            $this->line("[" . $this->now8() . "] 下次发言: {$sleepTime} 秒后");

            // 分段睡眠，以便能响应信号
            for ($i = 0; $i < $sleepTime && $this->shouldRun; $i++) {
                sleep(1);
            }
        }

        $this->newLine();
        $this->info("机器人守护进程已停止");

        return self::SUCCESS;
    }

    /**
     * 检查是否在安静时段
     * 使用 Asia/Shanghai (+8) 时区
     */
    private function isQuietHours(int $start, int $end): bool
    {
        // 使用 +8 时区
        $currentHour = (int) now('Asia/Shanghai')->format('G');

        if ($start <= $end) {
            // 正常范围，如 8-18 表示 8点到 18点
            return $currentHour >= $start && $currentHour < $end;
        }

        // 跨午夜的安静时段，如 start=23, end=7
        // 表示 23:00-23:59 和 00:00-06:59 是安静时段
        return $currentHour >= $start || $currentHour < $end;
    }

    /**
     * 获取当前 +8 时区时间字符串
     */
    private function now8(): string
    {
        return now('Asia/Shanghai')->format('H:i:s');
    }
}
