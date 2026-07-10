<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AppNotification extends Notification
{
    use Queueable;

    public string $title;
    public string $message;
    public string $url;
    public string $type;

    public function __construct(string $title, string $message, string $url = '', string $type = 'general')
    {
        $this->title = $title;
        $this->message = $message;
        $this->url = $url;
        $this->type = $type;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'type' => $this->type,
        ];
    }
}
