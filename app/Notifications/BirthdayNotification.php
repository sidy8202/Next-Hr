<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BirthdayNotification extends Notification
{
    use Queueable;

    public function __construct(public string $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'cake',
            'title' => 'Joyeux anniversaire !',
            'message' => $this->message,
            'url' => route('dashboard', absolute: false),
        ];
    }
}
