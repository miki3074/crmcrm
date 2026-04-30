<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProjectWatcherAddedNotification extends Notification
{
    use Queueable;

    protected $project;
    protected $company;

    public function __construct(Project $project, Company $company)
    {
        $this->project = $project;
        $this->company = $company;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("👁 Вас добавили наблюдателем проекта: {$this->project->name}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас добавили в качестве наблюдателя проекта.")
            ->line("**Проект:** {$this->project->name}")
            ->line("**Компания:** {$this->company->name}")
            ->line("Теперь вы будете получать уведомления о всех изменениях в проекте.")
            ->action("Перейти к проекту", url("/projects/{$this->project->id}"))
            ->salutation("С уважением, команда системы управления проектами");
    }
}
