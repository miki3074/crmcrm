<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Services\TelegramService;

class StagnantTaskReminder extends Notification
{
    use Queueable;

    protected $project, $tasks, $subtasks;

    public function __construct($project, $tasks, $subtasks)
    {
        $this->project = $project;
        $this->tasks = $tasks;
        $this->subtasks = $subtasks;
    }

    public function via($notifiable)
    {
        // 1. Отправляем в телеграм вручную, если есть ID
        if (!empty($notifiable->telegram_chat_id)) {
            $this->sendToTelegram($notifiable);
        }

        // 2. Возвращаем пустой массив, чтобы Laravel НЕ искал таблицу notifications
        return [];
    }

    protected function sendToTelegram($notifiable)
    {
        // Используем helper url(), чтобы точно получить полный путь с http/https
        $text = "<b>⚠️ Нулевой прогресс в проекте:</b> {$this->project->name}\n\n";

        if ($this->tasks->isNotEmpty()) {
            $text .= "<b>📌 Задачи:</b>\n";
            foreach ($this->tasks as $task) {
                // Формируем полную ссылку
                $link = url("/tasks/{$task->id}");
                $text .= "• <a href='{$link}'>{$task->title}</a>\n";
            }
        }

        if ($this->subtasks->isNotEmpty()) {
            $text .= "\n<b>🔗 Подзадачи:</b>\n";
            foreach ($this->subtasks as $sub) {
                // Ссылка на задачу, к которой относится подзадача
                $link = url("/tasks/{$sub->task_id}");
                $text .= "• <a href='{$link}'>{$sub->title}</a>\n";
            }
        }

        $text .= "\n<i>Пожалуйста, обновите статус или приступайте к работе!</i>";

        TelegramService::sendMessage($notifiable->telegram_chat_id, $text);
    }

    // Этот метод можно оставить пустым или удалить, если via возвращает []
    public function toArray($notifiable)
    {
        return [];
    }
}
