<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Notifications\TaskEventNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class StoreInAppNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $userId,
        public readonly TaskEventNotification $notification,
    ) {}

    public function handle(): void
    {
        $payload = $this->notification->toArray((object) ['id' => $this->userId]);

        Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $this->userId,
            'type' => $payload['type'],
            'title' => $payload['title'],
            'message' => $payload['message'],
            'data' => $payload['data'],
        ]);
    }
}
