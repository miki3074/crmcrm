<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CommentReplyNotification extends Notification
{
    use Queueable;

    protected $task;
    protected $comment;
    protected $parentComment;

    public function __construct(Task $task, TaskComment $comment)
    {
        $this->task = $task;
        $this->comment = $comment;
        $this->parentComment = $comment->parent;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("↩️ Ответ на ваш комментарий в задаче: " . $this->task->title)
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Пользователь {$this->comment->user->name} ответил на ваш комментарий:")
            ->line("**Ваш комментарий:**")
            ->line($this->parentComment->body)
            ->line("**Ответ:**")
            ->line($this->comment->body)
            ->line("**Задача:** {$this->task->title}")
            ->action("Перейти к задаче", url("/tasks/{$this->task->id}"))
            ->salutation("С уважением, система управления проектами");
    }
}
