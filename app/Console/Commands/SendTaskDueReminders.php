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

        $dateMinus3 = $today->copy()->addDays(3)->toDateString();
        $dateToday  = $today->toDateString();
        $datePlus3  = $today->copy()->subDays(3)->toDateString();

        $tasks = Task::with(['executors', 'responsibles'])
            ->where(function ($q) use ($dateMinus3) {
                $q->whereDate('due_date', $dateMinus3)->where('reminded_before3', false);
            })
            ->orWhere(function ($q) use ($dateToday) {
                $q->whereDate('due_date', $dateToday)->where('reminded_today', false);
            })
            ->orWhere(function ($q) use ($datePlus3) {
                $q->whereDate('due_date', $datePlus3)->where('reminded_after3', false);
            })
            ->get();

        foreach ($tasks as $task) {

            // --- Определяем, какая стадия ---
            if ($task->due_date->toDateString() === $dateMinus3) {
                $type = 'before3';
                $task->reminded_before3 = true;
            }
            elseif ($task->due_date->toDateString() === $dateToday) {
                $type = 'today';
                $task->reminded_today = true;
            }
            else {
                $type = 'after3';
                $task->reminded_after3 = true;
            }

            // --- Отправляем ---
            $this->sendReminderForTask($task, $type);

            // --- Фиксируем, что отправили ---
            $task->save();
        }

        $this->info('Напоминания отправлены.');
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

