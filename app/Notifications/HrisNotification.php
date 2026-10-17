<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class HrisNotification extends Notification
{
    public function __construct(
        private string $title,
        private string $body,
        private ?string $url = null,
        private string $icon = '•',
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'icon' => $this->icon,
        ];
    }
}
