<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserRemovedFromCompanyNotification extends Notification
{
    use Queueable;

    protected $company;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Определяем каналы доставки уведомления.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Формируем email сообщение.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Вас удалили из компании: {$this->company->name}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас удалили из компании **{$this->company->name}**.")
            ->line("Теперь вам недоступны проекты и задачи этой компании.")
            ->line("Если вы считаете, что это ошибка, свяжитесь с администратором.")
            ->salutation("С уважением, команда системы управления проектами");
    }
}
