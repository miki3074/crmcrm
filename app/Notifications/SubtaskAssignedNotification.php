<?php

namespace App\Notifications;

use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SubtaskAssignedNotification extends Notification
{
    use Queueable;

    protected $subtask;
    protected $task;
    protected $role; // 'executor', 'responsible'

    public function __construct(Subtask $subtask, Task $task, string $role)
    {
        $this->subtask = $subtask;
        $this->task = $task;
        $this->role = $role;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleText = $this->role === 'executor' ? 'исполнителем' : 'ответственным';
        $priorityText = [
            'low' => 'Низкий',
            'medium' => 'Средний',
            'high' => 'Высокий'
        ][$this->subtask->priority] ?? $this->subtask->priority;

        return (new MailMessage)
            ->subject("📋 Вас назначили {$roleText} подзадачи: " . $this->subtask->title)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас назначили **{$roleText}** подзадачи.")
            ->line("**Подзадача:** {$this->subtask->title}")
            ->line("**Основная задача:** {$this->task->title}")
            ->line("**Приоритет:** {$priorityText}")
            ->line("**Срок выполнения:** " . date('d.m.Y', strtotime($this->subtask->due_date ?? $this->task->due_date)))
            ->action("Перейти к подзадаче", url("/tasks/{$this->task->id}?subtask={$this->subtask->id}"))
            ->salutation("С уважением, система управления проектами");
    }
}
