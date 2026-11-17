<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    public function broadcastOn(): array
    {
        return ChatParticipant::query()
            ->where('conversation_id', $this->message->conversation_id)
            ->pluck('user_id')
            ->map(fn ($userId) => new PrivateChannel('chat.user.'.$userId))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing('sender.employee');

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->employee?->full_name ?: $this->message->sender?->name,
            'body' => $this->message->body,
            'attachment_name' => $this->message->attachment_name,
            'url' => route('chat.index', ['conversation' => $this->message->conversation_id]),
        ];
    }
}
