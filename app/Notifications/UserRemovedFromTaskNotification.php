<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserRemovedFromTaskNotification extends Notification
{
    use Queueable;

    protected $task;
    protected $role; // 'исполнителя', 'ответственного', 'наблюдателя'

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
        $roleText = $this->role === 'executor' ? 'исполнителя' :
            ($this->role === 'responsible' ? 'ответственного' : 'наблюдателя');

        return (new MailMessage)
            ->subject("⚠️ Вас исключили из задачи: " . $this->task->title)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Вас исключили из задачи в роли **{$roleText}**.")
            ->line("**Название задачи:** {$this->task->title}")
            ->line("**Приоритет:** " . $this->getPriorityText())
            ->line("**Срок выполнения:** " . date('d.m.Y', strtotime($this->task->due_date)))
            ->action("Посмотреть задачу", url("/tasks/{$this->task->id}"))
            ->line("Если вы считаете, что это ошибка, свяжитесь с руководителем проекта.")
            ->salutation("С уважением, система управления проектами");
    }

    private function getPriorityText(): string
    {
        return [
            'low' => 'Низкий',
            'medium' => 'Средний',
            'high' => 'Высокий'
        ][$this->task->priority] ?? $this->task->priority;
    }
}
