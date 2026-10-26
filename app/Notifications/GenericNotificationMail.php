<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Письмо для канала «почта» универсальных уведомлений (App\Notify).
 */
class GenericNotificationMail extends Notification
{
    public function __construct(private array $payload)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->payload['title'] ?: config('app.name'))
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line($this->payload['message']);

        if (!empty($this->payload['link'])) {
            $mail->action('Открыть', url($this->payload['link']));
        }

        return $mail;
    }
}
