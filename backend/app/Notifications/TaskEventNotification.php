<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskEventNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $eventType,
        public readonly string $title,
        public readonly string $message,
        public readonly array $data = [],
    ) {}

    /**
     * Delivery is handled by StoreInAppNotification because ProjectPulse keeps
     * a compact, API-specific notification table.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }
}
