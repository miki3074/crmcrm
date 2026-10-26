<?php

namespace App\Notify\Channels;

use App\Models\User;
use App\Notifications\GenericNotificationMail;
use Illuminate\Support\Facades\Notification;

class MailChannel
{
    public static function send(array $userIds, array $payload): void
    {
        $users = User::whereIn('id', $userIds)->whereNotNull('email')->get();

        if ($users->isEmpty()) {
            return;
        }

        // sendNow: доставка и так уже идёт из фоновой задачи
        Notification::sendNow($users, new GenericNotificationMail($payload));
    }
}
