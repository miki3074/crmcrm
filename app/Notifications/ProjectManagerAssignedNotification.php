<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProjectManagerAssignedNotification extends Notification
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
            ->subject("📢 Вас назначили руководителем проекта: {$this->project->name}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас назначили руководителем проекта в системе управления проектами.")
            ->line("**Проект:** {$this->project->name}")
            ->line("**Компания:** {$this->company->name}")
            ->line("**Дата начала:** " . date('d.m.Y', strtotime($this->project->start_date)))
            ->line("**Длительность:** {$this->project->duration_days} дней")
            ->line("**Плановая дата окончания:** " . date('d.m.Y', strtotime($this->project->start_date . ' + ' . $this->project->duration_days . ' days')))
            ->action("Перейти к проекту", url("/projects/{$this->project->id}"))
            ->line("Вам доступны все возможности управления проектом: создание задач, назначение исполнителей и контроль сроков.")

            ->salutation("С уважением, команда системы управления проектами");
    }
}
