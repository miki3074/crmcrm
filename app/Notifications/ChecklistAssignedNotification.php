<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskChecklist;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ChecklistAssignedNotification extends Notification
{
    use Queueable;

    protected $checklist;
    protected $task;

    public function __construct(TaskChecklist $checklist)
    {
        $this->checklist = $checklist;
        $this->task = $checklist->task;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $priorityText = [
            'low' => 'Низкий',
            'medium' => 'Средний',
            'high' => 'Высокий'
        ][$this->task->priority] ?? $this->task->priority;

        $mailMessage = (new MailMessage)
            ->subject("📝 Новый пункт чек-листа в задаче: " . $this->task->title)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("В задаче появился новый пункт чек-листа:")
            ->line("**{$this->checklist->title}**")
            ->line("**Задача:** {$this->task->title}")
            ->line("**Приоритет задачи:** {$priorityText}")
            ->line("**Срок выполнения:** " . date('d.m.Y', strtotime($this->task->due_date)));

        if ($this->checklist->important) {
            $mailMessage->line("⚠️ **Этот пункт отмечен как ВАЖНЫЙ!**");
        }

        if ($this->checklist->assigned_to) {
            $mailMessage->line("**Назначено:** {$notifiable->name}");
        }

        $mailMessage->action("Открыть задачу", url("/tasks/{$this->task->id}"))
            ->salutation("С уважением, система управления проектами");

        return $mailMessage;
    }
}
