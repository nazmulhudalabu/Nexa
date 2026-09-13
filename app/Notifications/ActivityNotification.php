<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $event,
        public User $actor,
        public string $message,
        public string $url,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
            'actor_photo' => $this->actor->profile_photo_url,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
