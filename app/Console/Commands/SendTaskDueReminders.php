<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\User;
use App\Services\TelegramService;
use Carbon\Carbon;

class SendTaskDueReminders extends Command
{
    protected $signature = 'tasks:due-reminders';
    protected $description = 'Отправка напоминаний о сроках задач в Telegram';

    public function handle()
    {
        $today = Carbon::today();

        $dateMinus3 = $today->copy()->addDays(3)->toDateString(); // через 3 дня истечёт
        $dateToday  = $today->toDateString();                     // сегодня дедлайн
        $datePlus3  = $today->copy()->subDays(3)->toDateString(); // был 3 дня назад

        // Загружаем задачи с нужными датами + исполнителей/ответственных
        $tasks = Task::with(['executors', 'responsibles'])
            ->whereIn('due_date', [$dateMinus3, $dateToday, $datePlus3])
            ->get();

        foreach ($tasks as $task) {
            // Определяем тип напоминания
            if ($task->due_date->toDateString() === $dateMinus3) {
                $type = 'before3';
            } elseif ($task->due_date->toDateString() === $dateToday) {
                $type = 'today';
            } else {
                $type = 'after3';
            }

            $this->sendReminderForTask($task, $type);
        }

        $this->info('Напоминания по задачам отправлены.');
        return 0;
    }

    protected function sendReminderForTask(Task $task, string $type)
    {
        // Собираем всех получателей: исполнители + ответственные
        $recipientIds = collect()
            ->merge($task->executors->pluck('id'))
            ->merge($task->responsibles->pluck('id'))
            ->unique()
            ->all();

        if (empty($recipientIds)) {
            return;
        }

        $users = User::whereIn('id', $recipientIds)->get();

        foreach ($users as $user) {
            if (!$user->telegram_chat_id) {
                continue;
            }

            $text = $this->buildMessageText($task, $type);

            TelegramService::sendMessage(
                $user->telegram_chat_id,
                $text
            );
        }
    }

    protected function buildMessageText(Task $task, string $type): string
    {
        $due = $task->due_date
            ? $task->due_date->format('d.m.Y')
            : 'не указан';

        switch ($type) {
            case 'before3':
                return "⏰ Через 3 дня истекает срок по задаче:\n"
                    . "<b>{$task->title}</b>\n"
                    . "Дедлайн: <b>{$due}</b>";

            case 'today':
                return "⚠️ Сегодня дедлайн по задаче:\n"
                    . "<b>{$task->title}</b>\n"
                    . "Срок: <b>{$due}</b>";

            case 'after3':
            default:
                return "❗ Прошло 3 дня после дедлайна задачи:\n"
                    . "<b>{$task->title}</b>\n"
                    . "Срок был: <b>{$due}</b>";
        }
    }
}

