<?php

namespace Tests\Feature;

use App\Livewire\Chat\ChatPage;
use App\Livewire\Chat\MessageBell;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ChatMessagingTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    public function test_company_users_can_create_a_direct_chat_send_text_and_share_a_file(): void
    {
        Storage::fake('local');
        $company = $this->makeCompany('chat-acme');
        $sender = $this->makeUser($company, owner: true);
        $recipient = $this->makeUser($company);

        $component = Livewire::actingAs($sender)->test(ChatPage::class)
            ->call('startDirect', $recipient->id)
            ->set('body', 'Bonjour 👋')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $conversation = ChatConversation::query()->firstOrFail();
        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Bonjour 👋',
        ]);

        $component->set('attachment', UploadedFile::fake()->create('guide.pdf', 25, 'application/pdf'))
            ->call('sendMessage')->assertHasNoErrors();

        $fileMessage = $conversation->messages()->whereNotNull('attachment_path')->firstOrFail();
        $this->assertSame('guide.pdf', $fileMessage->attachment_name);
        Storage::disk('local')->assertExists($fileMessage->attachment_path);
    }

    public function test_a_user_cannot_open_a_conversation_from_another_company(): void
    {
        $companyA = $this->makeCompany('chat-a');
        $companyB = $this->makeCompany('chat-b');
        $userA = $this->makeUser($companyA, owner: true);
        $userB = $this->makeUser($companyB, owner: true);

        $conversation = ChatConversation::withoutGlobalScopes()->forceCreate([
            'company_id' => $companyB->id,
            'created_by' => $userB->id,
            'type' => 'direct',
        ]);
        ChatParticipant::withoutGlobalScopes()->forceCreate([
            'company_id' => $companyB->id,
            'conversation_id' => $conversation->id,
            'user_id' => $userB->id,
        ]);

        Livewire::actingAs($userA)->test(ChatPage::class)
            ->call('selectConversation', $conversation->id)
            ->assertNotFound();
    }

    public function test_navbar_message_bell_lists_clickable_unread_conversations(): void
    {
        $company = $this->makeCompany('chat-navbar');
        $sender = $this->makeUser($company, owner: true);
        $reader = $this->makeUser($company, owner: true);
        $conversation = new ChatConversation();
        $conversation->forceFill(['company_id' => $company->id, 'created_by' => $sender->id, 'type' => 'direct'])->save();
        foreach ([[$sender, now()], [$reader, now()->subHour()]] as [$user, $lastReadAt]) {
            $participant = new ChatParticipant();
            $participant->forceFill([
                'company_id' => $company->id,
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
                'last_read_at' => $lastReadAt,
            ])->save();
        }
        $message = new ChatMessage();
        $message->forceFill([
            'company_id' => $company->id,
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'body' => 'Le récapitulatif est prêt pour validation.',
        ])->save();

        Livewire::actingAs($reader)->test(MessageBell::class)
            ->assertSee('Messages non lus')
            ->assertSee($sender->name)
            ->assertSee('Le récapitulatif est prêt pour validation.')
            ->assertSee(route('chat.index', ['conversation' => $conversation->id]));
    }
}
