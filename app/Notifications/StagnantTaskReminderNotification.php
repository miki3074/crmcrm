<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Carbon\Carbon;

class StagnantTaskReminderNotification extends Notification
{
    use Queueable;

    protected $project;
    protected $tasks;
    protected $subtasks;

    public function __construct(Project $project, $tasks, $subtasks)
    {
        $this->project = $project;
        $this->tasks = $tasks;
        $this->subtasks = $subtasks;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject("⚠️ Напоминание: Зависшие задачи в проекте {$this->project->name}")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Пожалуйста, обратите на них внимание и обновите статус.");

        // Добавляем список зависших задач
        if ($this->tasks->isNotEmpty()) {
            $mailMessage->line("")
                ->line("**📋 Задачи :**");

            foreach ($this->tasks as $task) {
                $mailMessage->line("")
                    ->line("▪ **{$task->title}**")
                    ->line("  • Срок: " . ($task->due_date ? Carbon::parse($task->due_date)->format('d.m.Y') : 'Не указан'))
                    ->line("  • Приоритет: " . $this->getPriorityText($task->priority))
                    ->action("Перейти к задаче", url("/tasks/{$task->id}"));
            }
        }

        // Добавляем список зависших подзадач
        if ($this->subtasks->isNotEmpty()) {
            $mailMessage->line("")
                ->line("**📌 Подзадачи :**");

            foreach ($this->subtasks as $subtask) {
                $mailMessage->line("")
                    ->line("▪ **{$subtask->title}**")
                    ->line("  • Основная задача: {$subtask->task->title}")
                    ->line("  • Срок: " . ($subtask->due_date ? Carbon::parse($subtask->due_date)->format('d.m.Y') : 'Не указан'))
                    ->line("  • Приоритет: " . $this->getPriorityText($subtask->priority))
                    ->action("Перейти к подзадаче", url("/tasks/{$subtask->task_id}?subtask={$subtask->id}"));
            }
        }

        $mailMessage->line("")
            ->line("Пожалуйста, обновите статус этих задач или назначьте ответственных.")
            ->salutation("С уважением, система управления проектами");

        return $mailMessage;
    }

    private function getPriorityText($priority)
    {
        return [
            'low' => 'Низкий',
            'medium' => 'Средний',
            'high' => 'Высокий'
        ][$priority] ?? $priority;
    }
}
