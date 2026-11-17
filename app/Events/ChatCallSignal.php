<?php

namespace App\Events;

use App\Models\ChatParticipant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ChatCallSignal implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $conversationId,
        public int $senderId,
        public string $senderName,
        public string $type,
        public array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return ChatParticipant::query()
            ->where('conversation_id', $this->conversationId)
            ->pluck('user_id')
            ->map(fn ($userId) => new PrivateChannel('chat.user.'.$userId))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'chat.call.signal';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'sender_id' => $this->senderId,
            'sender_name' => $this->senderName,
            'type' => $this->type,
            'payload' => $this->payload,
        ];
    }
}
