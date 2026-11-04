<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Avis d'abonnement envoyé au propriétaire de l'entreprise (notifications internes, lisibles dans « Notifications »). */
class SubscriptionNotice extends Notification
{
    public function __construct(public string $title, public string $message, public ?string $url = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'subscription', 'title' => $this->title, 'message' => $this->message, 'url' => $this->url];
    }
}
