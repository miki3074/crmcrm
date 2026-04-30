<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserAttachedToCompanyNotification extends Notification
{
    use Queueable;

    protected $company;
    protected $role;

    public function __construct(Company $company, string $role)
    {
        $this->company = $company;
        $this->role = $role;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleText = $this->role === 'manager' ? 'Менеджер' : ($this->role === 'employee' ? 'Сотрудник' : $this->role);

        return (new MailMessage)
            ->subject("Вас добавили в компанию: {$this->company->name}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас добавили в компанию **{$this->company->name}** в роли **{$roleText}**.")
            ->line("Теперь вам доступны проекты и задачи этой компании.")
            ->action("Перейти в компанию", url("/companies/{$this->company->id}"))
            ->line("Если у вас возникли вопросы, свяжитесь с администратором.")
            ->salutation("С уважением, команда системы управления проектами");
    }
}
