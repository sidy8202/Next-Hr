<?php

namespace App\Livewire\Chat;

use App\Services\ChatUnreadSummary;
use Livewire\Component;

class MessageBell extends Component
{
    public function render()
    {
        return view('livewire.chat.message-bell', app(ChatUnreadSummary::class)->for(auth()->user(), 5));
    }
}
