<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;

class ChatUnreadSummary
{
    /** @return array{count:int,items:\Illuminate\Support\Collection} */
    public function for(User $user, int $limit = 5): array
    {
        if (! $user->company_id || ! $user->canDo('read', 'team_chat')) {
            return ['count' => 0, 'items' => collect()];
        }

        $counts = ChatMessage::query()
            ->join('chat_conversation_participants as reader', 'reader.conversation_id', '=', 'chat_messages.conversation_id')
            ->where('reader.company_id', $user->company_id)
            ->where('reader.user_id', $user->id)
            ->where('chat_messages.sender_id', '<>', $user->id)
            ->where(fn ($query) => $query->whereNull('reader.last_read_at')
                ->orWhereColumn('chat_messages.created_at', '>', 'reader.last_read_at'))
            ->selectRaw('chat_messages.conversation_id, COUNT(*) as unread_count')
            ->groupBy('chat_messages.conversation_id')
            ->get()
            ->keyBy('conversation_id');

        if ($limit < 1 || $counts->isEmpty()) {
            return ['count' => (int) $counts->sum('unread_count'), 'items' => collect()];
        }

        $items = ChatConversation::query()
            ->whereIn('id', $counts->keys())
            ->with(['participants.user.employee', 'latestMessage.sender.employee'])
            ->get()
            ->map(function (ChatConversation $conversation) use ($counts, $user) {
                $others = $conversation->participants->reject(fn ($participant) => (int) $participant->user_id === (int) $user->id);
                $conversation->sidebar_title = $conversation->type === 'group'
                    ? $conversation->title
                    : ($others->map(fn ($participant) => $participant->user?->employee?->full_name ?: $participant->user?->name)->filter()->first() ?: 'Conversation');
                $conversation->unread_total = (int) $counts->get($conversation->id)->unread_count;
                $conversation->preview_sender = $conversation->latestMessage?->sender?->employee?->full_name
                    ?: $conversation->latestMessage?->sender?->name;
                $conversation->preview_text = $conversation->latestMessage?->body
                    ?: ($conversation->latestMessage?->attachment_name ? '📎 '.$conversation->latestMessage->attachment_name : 'Pièce jointe');

                return $conversation;
            })
            ->sortByDesc(fn ($conversation) => $conversation->latestMessage?->created_at)
            ->take($limit)
            ->values();

        return ['count' => (int) $counts->sum('unread_count'), 'items' => $items];
    }
}
