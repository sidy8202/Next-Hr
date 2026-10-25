<?php

namespace App\Notifications;

use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification RH générique (congés, documents, demandes…).
 * Compte actif : notification dans l'application (+ email). Sans compte : email seulement.
 * Données stockées : type, title, message, url — adaptez NotificationIndex si votre page attend d'autres clés.
 */
class HrEvent extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $url = null,
        public bool $mail = true,
    ) {
    }

    public function via(object $notifiable): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            return ['mail'];
        }

        return $this->mail ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->greeting('Bonjour,')->line($this->message);

        if ($this->url) {
            $mail->action('Ouvrir dans l’application', $this->url);
        }

        return $mail->salutation('Cordialement, '.config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => 'hr_event', 'title' => $this->title, 'message' => $this->message, 'url' => $this->url];
    }
}
