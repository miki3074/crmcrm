<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserAssignedToTaskNotification extends Notification
{
    use Queueable;

    protected $task;
    protected $role; // 'исполнитель', 'ответственный', 'наблюдатель'

    public function __construct(Task $task, string $role)
    {
        $this->task = $task;
        $this->role = $role;
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

        $roleText = $this->role === 'executor' ? 'исполнителем' :
            ($this->role === 'responsible' ? 'ответственным' : 'наблюдателем');

        return (new MailMessage)
            ->subject("📋 Вас назначили {$roleText} в задачу: " . $this->task->title)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас назначили **{$roleText}** в задачу.")
            ->line("**Название задачи:** {$this->task->title}")
            ->line("**Приоритет:** {$priorityText}")
            ->line("**Срок выполнения:** " . date('d.m.Y', strtotime($this->task->due_date)))
            ->line("**Описание:** " . ($this->task->description ?? 'Нет описания'))
            ->action("Открыть задачу", url("/tasks/{$this->task->id}"))
            ->line("Пожалуйста, ознакомьтесь с задачей и приступайте к выполнению.")
            ->salutation("С уважением, система управления проектами");
    }
}
