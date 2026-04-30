<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    protected $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
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

        return (new MailMessage)
            ->subject('📋 Вам назначена новая задача: ' . $this->task->title)
            ->greeting('Здравствуйте, ' . $notifiable->name . '!')
            ->line('Вам назначена новая задача в системе.')
            ->line('**Название задачи:** ' . $this->task->title)
            ->line('**Приоритет:** ' . $priorityText)
            ->line('**Срок выполнения:** ' . date('d.m.Y', strtotime($this->task->due_date)))
            ->action('Открыть задачу', url('/tasks/' . $this->task->id))
            ->line('Пожалуйста, не откладывайте выполнение задачи.')
            ->salutation('С уважением, система управления проектами');
    }
}
