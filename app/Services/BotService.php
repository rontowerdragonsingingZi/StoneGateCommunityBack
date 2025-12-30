<?php

namespace App\Services;

use App\Models\ApiConfig;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * 机器人核心服务
 * 处理消息分析、回复决策、主动发言等
 */
class BotService
{
    private DeepSeekService $deepSeek;

    // 回复概率配置
    private const REPLY_PROBABILITY_MENTIONED = 0.95;  // 被@或提及时
    private const REPLY_PROBABILITY_NORMAL = 0.15;     // 普通消息
    private const REPLY_PROBABILITY_QUESTION = 0.40;   // 看起来是问题
    private const REPLY_PROBABILITY_GREETING = 0.60;   // 问候语

    // 冷却时间（秒）
    private const COOLDOWN_SECONDS = 30;

    // 话题性对话配置
    private const MAX_CONSECUTIVE_REPLIES = 5;         // 最大连续回复次数
    private const FATIGUE_DECAY_SECONDS = 180;         // 疲劳度衰减时间（3分钟）
    private const TOPIC_COOLDOWN_SECONDS = 300;        // 话题冷却时间（5分钟）
    private const ACTIVITY_WINDOW_SECONDS = 300;       // 活跃度检测窗口（5分钟）

    public function __construct(DeepSeekService $deepSeek)
    {
        $this->deepSeek = $deepSeek;
    }

    /**
     * 获取所有机器人配置
     */
    public function getAllBotConfigs(): Collection
    {
        return ApiConfig::with('user')
            ->whereHas('user', fn ($q) => $q->where('is_bot', true))
            ->get();
    }

    /**
     * 获取单个机器人的配置
     */
    public function getBotConfig(int $userId): ?ApiConfig
    {
        return ApiConfig::whereHas('user', fn ($q) => $q->where('id', $userId)->where('is_bot', true))
            ->with('user')
            ->first();
    }

    /**
     * 判断机器人是否应该回复该消息
     * 包含话题性对话机制：疲劳度、话题冷却、活跃度
     *
     * @param Message $message 收到的消息
     * @param User $bot 机器人用户
     * @return bool
     */
    public function shouldReply(Message $message, User $bot): bool
    {
        // 不回复自己的消息
        if ($message->user_id === $bot->id) {
            return false;
        }

        // 机器人可以回复其他机器人，但概率较低（避免无限对话）
        $sender = User::find($message->user_id);
        $isBotToBot = $sender && $sender->is_bot;
        if ($isBotToBot) {
            // 机器人之间对话概率降低，且仍受疲劳度/话题冷却影响
            // 基础概率 25%，后续会被疲劳度进一步降低
        }

        // 不回复系统消息
        if ($message->type === 'system') {
            return false;
        }

        // 检查基础冷却时间
        if ($this->isOnCooldown($bot->id, $message->channel)) {
            return false;
        }

        // 检查话题冷却（连续发言太多后强制休息）
        if ($this->isInTopicCooldown($bot->id, $message->channel)) {
            return false;
        }

        $content = $message->content;

        // 机器人之间对话用较低的基础概率
        if ($isBotToBot) {
            $baseProbability = 0.25; // 25% 基础概率
        } else {
            $baseProbability = self::REPLY_PROBABILITY_NORMAL;
        }

        // 被直接提及或@
        if ($this->isMentioned($content, $bot->name)) {
            $baseProbability = $isBotToBot ? 0.6 : self::REPLY_PROBABILITY_MENTIONED;
        }
        // 问候语
        elseif ($this->isGreeting($content)) {
            $baseProbability = $isBotToBot ? 0.35 : self::REPLY_PROBABILITY_GREETING;
        }
        // 看起来是问题
        elseif ($this->isQuestion($content)) {
            $baseProbability = $isBotToBot ? 0.30 : self::REPLY_PROBABILITY_QUESTION;
        }

        // 应用疲劳度调整（连续发言越多，回复概率越低）
        $fatigue = $this->getFatigue($bot->id, $message->channel);
        $fatigueMultiplier = max(0.1, 1 - ($fatigue * 0.2)); // 每次发言减少20%概率
        $adjustedProbability = $baseProbability * $fatigueMultiplier;

        // 根据频道活跃度调整（很活跃时机器人可以少说点）
        $activity = $this->getChannelActivity($message->channel);
        if ($activity > 10) { // 5分钟内超过10条消息
            $adjustedProbability *= 0.7; // 活跃时减少发言
        }

        return $this->rollDice($adjustedProbability);
    }

    /**
     * 生成回复消息
     *
     * @param Message $message 要回复的消息
     * @param User $bot 机器人用户
     * @param string $apiKey API 密钥
     * @return string|null
     */
    public function generateReply(Message $message, User $bot, string $apiKey): ?string
    {
        // 获取上下文消息
        $contextMessages = $this->getContextMessages($message->channel, 10, $message->id);

        // 构建对话历史
        $messages = $contextMessages->map(fn ($msg) => [
            'role' => $msg->user_id === $bot->id ? 'assistant' : 'user',
            'content' => $this->formatMessageForAI($msg),
        ])->values()->toArray();

        // 添加当前消息
        $messages[] = [
            'role' => 'user',
            'content' => $this->formatMessageForAI($message),
        ];

        // 构建系统提示词
        $systemPrompt = $this->buildSystemPrompt($bot, $message->channel);

        // 调用 AI 生成回复
        $reply = $this->deepSeek->chat($apiKey, $messages, $systemPrompt, [
            'temperature' => 0.85,
            'max_tokens' => 300,
        ]);

        if ($reply) {
            // 设置基础冷却
            $this->setCooldown($bot->id, $message->channel);

            // 增加疲劳度
            $this->increaseFatigue($bot->id, $message->channel);

            // 检查是否需要进入话题冷却
            $this->checkTopicCooldown($bot->id, $message->channel);
        }

        return $reply;
    }

    /**
     * 主动发起聊天（用于守护进程）
     * 只在频道不活跃时才主动发起话题
     *
     * @param string $channel 频道名
     * @return array|null ['bot' => User, 'content' => string]
     */
    public function initiateChat(string $channel = 'lobby'): ?array
    {
        // 检查频道活跃度 - 如果已经很活跃，不需要主动发起话题
        $activity = $this->getChannelActivity($channel);
        if ($activity > 3) { // 5分钟内超过3条消息，认为活跃
            Log::debug('Channel active, skip initiate chat', ['channel' => $channel, 'activity' => $activity]);
            return null;
        }

        // 获取所有机器人配置
        $configs = $this->getAllBotConfigs();
        if ($configs->isEmpty()) {
            return null;
        }

        // 过滤掉在话题冷却中的机器人
        $availableConfigs = $configs->filter(function ($config) use ($channel) {
            return !$this->isInTopicCooldown($config->user->id, $channel)
                && !$this->isOnCooldown($config->user->id, $channel, 120);
        });

        if ($availableConfigs->isEmpty()) {
            return null;
        }

        // 随机选择一个可用的机器人
        $config = $availableConfigs->random();
        $bot = $config->user;

        // 获取最近的消息作为上下文
        $recentMessages = $this->getContextMessages($channel, 5);

        // 构建提示
        $messages = [];
        if ($recentMessages->isNotEmpty()) {
            foreach ($recentMessages as $msg) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $this->formatMessageForAI($msg),
                ];
            }
        }

        // 添加发言指令
        $messages[] = [
            'role' => 'user',
            'content' => '[系统提示：请根据你的性格，在聊天室中主动发起一个话题或发表评论。可以是打招呼、分享想法、提问等。保持简短自然。]',
        ];

        $systemPrompt = $this->buildSystemPrompt($bot, $channel);

        $content = $this->deepSeek->chat($config->api_key, $messages, $systemPrompt, [
            'temperature' => 0.9,
            'max_tokens' => 200,
        ]);

        if ($content) {
            $this->setCooldown($bot->id, $channel);
            return [
                'bot' => $bot,
                'content' => $content,
                'api_key' => $config->api_key,
            ];
        }

        return null;
    }

    /**
     * 发送机器人消息到频道
     */
    public function sendMessage(User $bot, string $channel, string $content, string $type = 'text'): Message
    {
        $message = Message::create([
            'user_id' => $bot->id,
            'channel' => $channel,
            'content' => $content,
            'type' => $type,
        ]);

        // 广播消息
        broadcast(new MessageSent($message, $bot))->toOthers();

        return $message;
    }

    // ==================== 预留扩展接口 ====================

    /**
     * 发送表情消息（预留）
     */
    public function sendSticker(User $bot, string $channel, string $stickerUrl): Message
    {
        return $this->sendMessage($bot, $channel, $stickerUrl, 'sticker');
    }

    /**
     * 发送私聊消息（预留）
     */
    public function sendPrivateMessage(User $bot, int $friendId, string $content): ?Message
    {
        // TODO: 实现私聊逻辑
        Log::info('Bot private message (not implemented)', [
            'bot_id' => $bot->id,
            'friend_id' => $friendId,
            'content' => $content,
        ]);
        return null;
    }

    /**
     * 获取/存储对话记忆（预留）
     */
    public function getMemory(User $bot, int $userId): array
    {
        // TODO: 实现记忆化存储
        return Cache::get("bot_memory:{$bot->id}:{$userId}", []);
    }

    public function setMemory(User $bot, int $userId, array $memory): void
    {
        // TODO: 实现记忆化存储
        Cache::put("bot_memory:{$bot->id}:{$userId}", $memory, 86400 * 7);
    }

    // ==================== 私有方法 ====================

    /**
     * 判断消息是否提及了机器人
     */
    private function isMentioned(string $content, string $botName): bool
    {
        $patterns = [
            $botName,
            str_replace('_', ' ', $botName),
            '@' . $botName,
        ];

        // 添加角色别名
        $aliases = $this->getBotAliases($botName);
        $patterns = array_merge($patterns, $aliases);

        foreach ($patterns as $pattern) {
            if (stripos($content, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取机器人别名
     */
    private function getBotAliases(string $botName): array
    {
        $aliases = [
            'Okabe_Rintaro' => ['冈部', '伦太郎', '凤凰院', '凶真', 'okabe'],
            'Makise_Kurisu' => ['红莉栖', '助手', '克里斯蒂娜', 'kurisu', '牧濑'],
            'Shiina_Mayuri' => ['真由理', '真由氏', 'mayuri', '椎名'],
            'Hashida_Itaru' => ['桶子', 'DARU', '达鲁', 'daru', '桥田'],
            'Amane_Suzuha' => ['铃羽', 'suzuha', '阿万音'],
            'Urushibara_Ruka' => ['琉华', 'ruka', '漆原'],
            'Faris_NyanNyan' => ['菲利斯', '喵喵', 'faris'],
            'Moeka_Kiryuu' => ['萌郁', 'moeka', '桐生'],
        ];

        return $aliases[$botName] ?? [];
    }

    /**
     * 判断是否是问候语
     */
    private function isGreeting(string $content): bool
    {
        $greetings = ['你好', '嗨', 'hi', 'hello', '早', '晚上好', '大家好', '哈喽', '在吗'];
        $content = strtolower($content);

        foreach ($greetings as $greeting) {
            if (stripos($content, $greeting) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断是否是问题
     */
    private function isQuestion(string $content): bool
    {
        $patterns = ['?', '？', '吗', '呢', '什么', '怎么', '为什么', '如何', '谁', '哪'];

        foreach ($patterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * 概率判断
     */
    private function rollDice(float $probability): bool
    {
        return mt_rand(1, 100) <= ($probability * 100);
    }

    /**
     * 检查冷却状态
     */
    private function isOnCooldown(int $botId, string $channel, int $seconds = null): bool
    {
        $seconds = $seconds ?? self::COOLDOWN_SECONDS;
        $key = "bot_cooldown:{$botId}:{$channel}";
        return Cache::has($key);
    }

    /**
     * 设置冷却
     */
    private function setCooldown(int $botId, string $channel): void
    {
        $key = "bot_cooldown:{$botId}:{$channel}";
        Cache::put($key, true, self::COOLDOWN_SECONDS);
    }

    // ==================== 话题性对话机制 ====================

    /**
     * 获取疲劳度（0-5）
     */
    private function getFatigue(int $botId, string $channel): int
    {
        $key = "bot_fatigue:{$botId}:{$channel}";
        return (int) Cache::get($key, 0);
    }

    /**
     * 增加疲劳度
     */
    private function increaseFatigue(int $botId, string $channel): void
    {
        $key = "bot_fatigue:{$botId}:{$channel}";
        $current = $this->getFatigue($botId, $channel);
        Cache::put($key, min($current + 1, self::MAX_CONSECUTIVE_REPLIES), self::FATIGUE_DECAY_SECONDS);
    }

    /**
     * 检查是否在话题冷却中
     */
    private function isInTopicCooldown(int $botId, string $channel): bool
    {
        $key = "bot_topic_cooldown:{$botId}:{$channel}";
        return Cache::has($key);
    }

    /**
     * 检查并设置话题冷却（如果疲劳度达到上限）
     */
    private function checkTopicCooldown(int $botId, string $channel): void
    {
        $fatigue = $this->getFatigue($botId, $channel);
        if ($fatigue >= self::MAX_CONSECUTIVE_REPLIES) {
            // 进入话题冷却
            $key = "bot_topic_cooldown:{$botId}:{$channel}";
            Cache::put($key, true, self::TOPIC_COOLDOWN_SECONDS);

            // 重置疲劳度
            Cache::forget("bot_fatigue:{$botId}:{$channel}");

            Log::info('Bot entered topic cooldown', [
                'bot_id' => $botId,
                'channel' => $channel,
                'cooldown_seconds' => self::TOPIC_COOLDOWN_SECONDS,
            ]);
        }
    }

    /**
     * 获取频道活跃度（最近N分钟的消息数）
     */
    private function getChannelActivity(string $channel): int
    {
        $since = now()->subSeconds(self::ACTIVITY_WINDOW_SECONDS);
        return Message::where('channel', $channel)
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * 获取上下文消息
     */
    private function getContextMessages(string $channel, int $limit = 10, ?int $beforeId = null): Collection
    {
        $query = Message::where('channel', $channel)
            ->with('user:id,name,is_bot')
            ->orderBy('id', 'desc');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        return $query->limit($limit)->get()->reverse()->values();
    }

    /**
     * 格式化消息供 AI 使用
     */
    private function formatMessageForAI(Message $message): string
    {
        $userName = $message->user?->name ?? '未知用户';
        return "[{$userName}]: {$message->content}";
    }

    /**
     * 构建系统提示词
     */
    private function buildSystemPrompt(User $bot, string $channel): string
    {
        $basePrompt = <<<PROMPT
你正在一个名为"命运石之门社区"的聊天室中，频道是 {$channel}。
这是一个轻松友好的社区，成员们经常讨论各种话题。

重要规则：
1. 保持角色扮演，始终以你的人格说话
2. 回复要简短自然，像真人聊天一样（通常1-3句话）
3. 不要解释你是AI或机器人
4. 可以使用表情符号，但不要过度
5. 如果话题不适合或不感兴趣，可以简短回应或转移话题
6. 偶尔可以不回复（保持沉默）

PROMPT;

        $personality = $bot->personality ?? '';

        return $basePrompt . "\n你的人格设定：\n" . $personality;
    }
}
