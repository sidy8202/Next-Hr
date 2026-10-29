<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * Notification interne (base de données).
 * À ALIGNER avec le format lu par ta page notifications.index (clés title / message / url).
 */
class MissionNotification extends Notification
{
    public function __construct(
        public int $missionId,
        public string $title,
        public string $message,
        public bool $withLink = true,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mission',
            'mission_id' => $this->missionId,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->withLink ? route('missions.show', $this->missionId, false) : null, // chemin relatif : exigé par NotificationIndex::open()
        ];
    }
}
