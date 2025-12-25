<?php

namespace App\Events;

use App\Models\PrivateMessage;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PrivateMessage $message;
    public User $sender;

    /**
     * Create a new event instance.
     */
    public function __construct(PrivateMessage $message, User $sender)
    {
        $this->message = $message;
        $this->sender = $sender;
    }

    /**
     * 广播到的频道（私有频道）
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('private-chat.' . $this->message->conversation_id),
        ];
    }

    /**
     * 广播的事件名称
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * 广播的数据
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'created_at' => $this->message->created_at->toIso8601String(),
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
                'avatar' => $this->sender->avatar,
            ],
        ];
    }
}
