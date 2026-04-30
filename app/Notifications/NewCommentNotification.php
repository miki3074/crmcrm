<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewCommentNotification extends Notification
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
            ->subject("💬 Новое сообщение в задаче: " . $this->task->title)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Новое сообщение в задаче, в которой вы участвуете:")
            ->line("**Задача:** {$this->task->title}")
            ->line("**Автор:** {$this->comment->user->name}")
            ->line("**Сообщение:**")
            ->line($this->comment->body)
            ->action("Перейти к задаче", url("/tasks/{$this->task->id}"))
            ->salutation("С уважением, система управления проектами");
    }
}
