<?php

namespace App\Notifications;

use App\Models\Company;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ProjectManagerReplacedNotification extends Notification
{
    use Queueable;

    protected $project;
    protected $company;
    protected $oldManagerName;

    public function __construct(Project $project, Company $company, string $oldManagerName)
    {
        $this->project = $project;
        $this->company = $company;
        $this->oldManagerName = $oldManagerName;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("👔 Вас назначили руководителем проекта: {$this->project->name}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вы назначены руководителем проекта вместо {$this->oldManagerName}.")
            ->line("**Проект:** {$this->project->name}")
            ->line("**Компания:** {$this->company->name}")
            ->line("**Дата начала:** " . date('d.m.Y', strtotime($this->project->start_date)))
            ->action("Перейти к проекту", url("/projects/{$this->project->id}"))
            ->line("Вам доступны все возможности управления проектом.")
            ->salutation("С уважением, команда системы управления проектами");
    }
}
