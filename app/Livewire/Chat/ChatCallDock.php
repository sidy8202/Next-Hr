<?php

namespace App\Livewire\Chat;

use App\Events\ChatCallSignal;
use App\Models\ChatConversation;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatCallDock extends Component
{
    #[On('send-chat-call-signal')]
    public function sendCallSignal(int $conversationId, string $type, array $payload = []): void
    {
        abort_unless(auth()->user()?->canDo('read', 'team_chat'), 403);
        abort_unless(in_array($type, ['offer', 'answer', 'candidate', 'hangup', 'reject'], true), 422);
        $conversation = ChatConversation::query()->whereKey($conversationId)
            ->where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->where('user_id', auth()->id()))
            ->firstOrFail();

        $rules = match ($type) {
            'offer', 'answer' => ['payload.sdp' => ['required', 'string', 'max:100000']],
            'candidate' => ['payload.candidate' => ['required', 'string', 'max:4096'],
                'payload.sdpMid' => ['nullable', 'string', 'max:100'],
                'payload.sdpMLineIndex' => ['nullable', 'integer']],
            default => [],
        };
        if ($rules) Validator::make(['payload' => $payload], $rules)->validate();

        $user = auth()->user();
        broadcast(new ChatCallSignal($conversation->id, (int) $user->id,
            $user->employee?->full_name ?: $user->name, $type, $payload));
    }

    public function render()
    {
        return view('livewire.chat.chat-call-dock', [
            'iceServers' => config('chat.ice_servers', []),
        ]);
    }
}
