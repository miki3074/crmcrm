<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $code
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Код подтверждения входа')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line('Для входа в систему используйте код:')
            ->line("**{$this->code}**")
            ->line('Код действует 10 минут.')
            ->line('Если вы не пытались войти, просто проигнорируйте это письмо.')
            ->salutation('С уважением, система управления проектами');
    }
}