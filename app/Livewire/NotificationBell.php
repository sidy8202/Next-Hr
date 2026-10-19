<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    #[Computed]
    public function latest()
    {
        return auth()->user()->notifications()->latest()->limit(10)->get();
    }

    /** Marque comme lue puis ouvre le lien de la notification. */
    public function open(string $id)
    {
        $notification = auth()->user()->notifications()->whereKey($id)->first(); // uniquement SES notifications

        if (! $notification) {
            return null;
        }

        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        // On n'accepte que des liens internes (commençant par un seul « / »).
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
        return view('livewire.notification-bell');
    }
}
