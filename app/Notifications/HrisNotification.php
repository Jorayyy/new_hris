<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HrisNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $body,
        private ?string $url = null,
        private string $icon = '•',
        private bool $mail = false,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->mail ? ['database', 'mail'] : ['database'];
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

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->title)
            ->line($this->body);

        if ($this->url) {
            $message->action('View in '.config('app.name'), $this->url);
        }

        return $message->salutation(config('app.name'));
    }
}
