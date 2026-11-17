<?php

namespace App\Livewire\Chat;

use App\Services\ChatUnreadSummary;
use Livewire\Component;

class SidebarUnread extends Component
{
    public function unreadData(): array
    {
        return app(ChatUnreadSummary::class)->for(auth()->user(), limit: 0);
    }

    public function render()
    {
        return view('livewire.chat.sidebar-unread', $this->unreadData());
    }
}
