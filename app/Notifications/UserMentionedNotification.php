<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserMentionedNotification extends Notification
{
    use Queueable;

    protected $task;
    protected $comment;

    public function __construct(Task $task, TaskComment $comment)
    {
        $this->task = $task;
        $this->comment = $comment;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("📣 Вас упомянули в задаче: " . $this->task->title)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Пользователь {$this->comment->user->name} упомянул вас в комментарии:")
            ->line("**Сообщение:**")
            ->line($this->comment->body)
            ->line("**Задача:** {$this->task->title}")
            ->action("Перейти к задаче", url("/tasks/{$this->task->id}"))
            ->salutation("С уважением, система управления проектами");
    }
}
