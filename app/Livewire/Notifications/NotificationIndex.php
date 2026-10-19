<?php

namespace App\Livewire\Notifications;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Notifications')]
class NotificationIndex extends Component
{
    use WithPagination;

    #[Url(as: 'filtre')]
    public string $filter = 'all';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function open(string $id)
    {
        $notification = auth()->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return null;
        }

        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        return ($url && str_starts_with($url, '/') && ! str_starts_with($url, '//'))
            ? $this->redirect($url, navigate: true)
            : null;
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $query = $this->filter === 'unread' ? auth()->user()->unreadNotifications() : auth()->user()->notifications();

        return view('livewire.notifications.notification-index', [
            'notifications' => $query->latest()->paginate(15),
        ]);
    }
}
