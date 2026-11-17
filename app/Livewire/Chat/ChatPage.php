<?php
namespace App\Livewire\Chat;
use App\Events\ChatMessageSent;
use App\Livewire\Concerns\AuthorizesResource;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Messagerie')]
class ChatPage extends Component
{
    use AuthorizesResource;
    use WithFileUploads;

    #[Locked] public ?int $conversationId = null;
    #[Locked] public ?int $observedMessageId = null;
    public string $search = '';
    public string $personSearch = '';
    public string $groupTitle = '';
    public array $memberIds = [];
    public string $body = '';
    public ?TemporaryUploadedFile $attachment = null;

    public function mount(): void
    {
        $this->authorizeAction('read', 'team_chat');
        if (($id = request()->integer('conversation')) > 0) $this->selectConversation($id);
    }

    #[Computed]
    public function conversations()
    {
        return ChatConversation::whereHas('participants', fn ($q) => $q->where('user_id', auth()->id()))
            ->with(['participants.user.employee', 'latestMessage.sender.employee'])
            ->orderByDesc('updated_at')->limit(80)->get();
    }

    #[Computed]
    public function currentConversation(): ?ChatConversation
    {
        return $this->conversationId
            ? ChatConversation::whereKey($this->conversationId)
                ->whereHas('participants', fn ($q) => $q->where('user_id', auth()->id()))
                ->with('participants.user.employee')->first()
            : null;
    }

    #[Computed]
    public function messages()
    {
        return $this->currentConversation?->messages()->with('sender.employee')
            ->latest('id')->limit(100)->get()->reverse()->values() ?? collect();
    }

    #[Computed]
    public function people()
    {
        $term = trim($this->personSearch);
        return User::where('company_id', auth()->user()->company_id)->where('is_active', true)
            ->where('id', '<>', auth()->id())->with('employee:id,company_id,first_name,last_name,matricule')
            ->when(mb_strlen($term) >= 2, fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('name', 'like', '%'.$term.'%')->orWhere('email', 'like', $term.'%')
                    ->orWhereHas('employee', fn ($e) => $e->where('first_name', 'like', '%'.$term.'%')
                        ->orWhere('last_name', 'like', '%'.$term.'%')->orWhere('matricule', 'like', $term.'%'));
            }))->orderBy('name')->limit(25)->get();
    }

    public function selectConversation(int $id): void
    {
        $this->authorizeAction('read', 'team_chat');
        $item = ChatConversation::whereKey($id)
            ->whereHas('participants', fn ($q) => $q->where('user_id', auth()->id()))->firstOrFail();
        $this->conversationId = $item->id;
        $this->markRead();
        $this->observedMessageId = $item->latestMessage?->id;
        unset($this->currentConversation, $this->messages, $this->conversations);
    }

    public function startDirect(int $userId): void
    {
        $this->authorizeAction('create', 'team_chat');
        $target = User::where('company_id', auth()->user()->company_id)
            ->where('is_active', true)->findOrFail($userId);
        abort_if((int) $target->id === (int) auth()->id(), 422);

        $item = DB::transaction(function () use ($target) {
            $existing = ChatConversation::where('type', 'direct')
                ->whereHas('participants', fn ($q) => $q->where('user_id', auth()->id()))
                ->whereHas('participants', fn ($q) => $q->where('user_id', $target->id))
                ->withCount('participants')->get()
                ->first(fn ($c) => $c->participants_count === 2);
            if ($existing) return $existing;

            $item = new ChatConversation();
            $item->forceFill(['company_id' => auth()->user()->company_id, 'created_by' => auth()->id(), 'type' => 'direct'])->save();
            $this->addParticipant($item, (int) auth()->id());
            $this->addParticipant($item, (int) $target->id);
            return $item;
        });

        $this->personSearch = '';
        $this->selectConversation($item->id);
    }

    public function startGroup(): void
    {
        $this->authorizeAction('create', 'team_chat');
        $this->validate([
            'groupTitle' => ['required', 'string', 'max:120'],
            'memberIds' => ['required', 'array', 'min:2'],
            'memberIds.*' => ['integer', 'distinct', Rule::exists('users', 'id')
                ->where('company_id', auth()->user()->company_id)->where('is_active', true)],
        ]);
        abort_if(in_array((int) auth()->id(), array_map('intval', $this->memberIds), true), 422);

        $item = DB::transaction(function () {
            $item = new ChatConversation();
            $item->forceFill(['company_id' => auth()->user()->company_id, 'created_by' => auth()->id(),
                'type' => 'group', 'title' => trim($this->groupTitle)])->save();
            $this->addParticipant($item, (int) auth()->id());
            foreach (array_unique(array_map('intval', $this->memberIds)) as $id) $this->addParticipant($item, $id);
            return $item;
        });
        $this->reset('groupTitle', 'memberIds', 'personSearch');
        $this->selectConversation($item->id);
    }

    public function sendMessage(): void
    {
        $this->authorizeAction('create', 'team_chat');
        $item = $this->currentConversation;
        abort_unless($item && $item->participants()->where('user_id', auth()->id())->exists(), 403);
        $this->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,webp,doc,docx,xls,xlsx,txt', 'max:10240'],
        ]);
        $body = trim($this->body);
        if ($body === '' && ! $this->attachment) { $this->addError('body', 'Écrivez un message ou joignez un fichier.'); return; }

        $message = new ChatMessage();
        $path = $this->attachment?->store("companies/".auth()->user()->company_id.'/chat/'.$item->id, 'local');
        $message->forceFill(['company_id' => auth()->user()->company_id, 'conversation_id' => $item->id,
            'sender_id' => auth()->id(), 'body' => $body ?: null,
            'attachment_path' => $path,
            'attachment_name' => $this->attachment ? mb_substr($this->attachment->getClientOriginalName(), 0, 180) : null,
        ])->save();
        $item->touch();
        broadcast(new ChatMessageSent($message))->toOthers();
        $this->reset('body', 'attachment');
        $this->markRead();
        $this->dispatch('chat-sent');
        unset($this->messages, $this->conversations);
    }

    public function addEmoji(string $emoji): void
    {
        abort_unless(in_array($emoji, ['😀', '😂', '😊', '😍', '👍', '👏', '🎉', '🙏', '❤️', '😢', '🤔', '🔥'], true), 422);
        $this->body .= $emoji;
    }

    public function refreshChat(): void
    {
        if (!$this->conversationId) return;
        $conversation = $this->currentConversation;
        abort_unless($conversation, 404);
        $latest = $conversation->latestMessage;
        if ($latest && $latest->id !== $this->observedMessageId) {
            $wasAlreadyObserved = $this->observedMessageId === null;
            $this->observedMessageId = $latest->id;
            if (! $wasAlreadyObserved && (int) $latest->sender_id !== (int) auth()->id()) {
                $sender = $latest->sender?->employee?->full_name ?: $latest->sender?->name ?: 'Nouveau message';
                $this->dispatch('chat-alert', title: $sender,
                    body: $latest->body ?: ($latest->attachment_name ?: 'Pièce jointe'),
                    messageId: $latest->id,
                    url: route('chat.index', ['conversation' => $conversation->id]));
            }
        }
        $this->markRead();
        unset($this->messages, $this->conversations);
    }

    #[On('chat-message-received')]
    public function receiveBroadcastMessage(int $conversationId, int $messageId): void
    {
        if ($this->conversationId !== $conversationId || ! $this->currentConversation) return;
        $message = ChatMessage::query()->whereKey($messageId)->where('conversation_id', $conversationId)->first();
        if (! $message || (int) $message->sender_id === (int) auth()->id()) return;
        $this->observedMessageId = $message->id;
        $this->markRead();
        unset($this->messages, $this->conversations);
    }

    private function addParticipant(ChatConversation $item, int $userId): void
    {
        $participant = new ChatParticipant();
        $participant->forceFill(['company_id' => auth()->user()->company_id,
            'conversation_id' => $item->id, 'user_id' => $userId])->save();
    }

    private function markRead(): void
    {
        if ($this->conversationId && $this->currentConversation) {
            ChatParticipant::where('conversation_id', $this->conversationId)
                ->where('user_id', auth()->id())->update(['last_read_at' => now()]);
        }
    }

    private function displayName(?User $user): string
    {
        return $user?->employee?->full_name ?: ($user?->name ?? '');
    }

    public function render()
    {
        $conversations = $this->conversations;
        $term = mb_strtolower(trim($this->search));
        if ($term !== '') {
            $conversations = $conversations->filter(function ($c) use ($term) {
                $names = $c->participants->reject(fn ($p) => (int) $p->user_id === (int) auth()->id())
                    ->map(fn ($p) => $this->displayName($p->user))->implode(' ');
                return str_contains(mb_strtolower(($c->title ?? '').' '.$names), $term);
            })->values();
        }

        $unread = [];
        foreach ($conversations as $c) {
            $participant = $c->participants->firstWhere('user_id', auth()->id());
            $unread[$c->id] = $participant ? $c->messages()->where('sender_id', '<>', auth()->id())
                ->when($participant->last_read_at, fn ($q) => $q->where('created_at', '>', $participant->last_read_at))->count() : 0;
        }
        return view('livewire.chat.chat-page', compact('conversations', 'unread'));
    }
}
