<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserAccountCreatedNotification extends Notification
{
    use Queueable;

    protected $password;
    protected $companyName;

    public function __construct(string $password, string $companyName)
    {
        $this->password = $password;
        $this->companyName = $companyName;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Добро пожаловать! Учетная запись создана")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Для вас была создана учетная запись в системе управления проектами.")
            ->line("**Компания:** {$this->companyName}")
            ->line("**Email для входа:** {$notifiable->email}")
            ->line("**Пароль для входа:** {$this->password}")
            ->action("Войти в систему", url('/login'))
            ->line("Рекомендуем сменить пароль после первого входа.")
            ->line("Если у вас возникли вопросы, обратитесь к администратору системы.")
            ->salutation("С уважением, команда системы управления проектами");
    }
}
