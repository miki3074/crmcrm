<?php

namespace App\Console\Commands;

use App\Models\KlientDeal;
use App\Models\KlientTask;
use App\Models\SiteNotification;
use App\Services\SiteNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * Напоминания о сроках по клиентам (уведомления на сайте):
 * - задача клиента: срок сегодня / просрочена — ответственному;
 * - сделка: срок завтра / просрочена — ответственным.
 *
 * Запускается каждый час. Повторы отсекаются по уже созданным
 * уведомлениям (тип + ссылка), поэтому каждое напоминание приходит один раз.
 */
class SendKlientDueReminders extends Command
{
    protected $signature = 'klients:due-reminders';
    protected $description = 'Напоминания о сроках задач клиентов и сделок (уведомления на сайте)';

    private const CLOSED_DEAL_STATUSES = ['Успешно', 'Отказ'];

    public function handle()
    {
        $now = Carbon::now();

        $this->remindTasks($now);
        $this->remindDeals($now);

        $this->info('Напоминания по клиентам отправлены.');
    }

    private function remindTasks(Carbon $now): void
    {
        $tasks = KlientTask::with('klient:id,name')
            ->whereIn('status', ['pending', 'processing'])
            ->whereNotNull('deadline')
            ->whereNotNull('responsible_id')
            ->where('deadline', '<=', $now->copy()->endOfDay())
            ->get();

        foreach ($tasks as $task) {
            $deadline = Carbon::parse($task->deadline);
            $link = "/klient-tasks/{$task->id}";
            $klientName = $task->klient?->name;

            if ($deadline->lt($now)) {
                // Просрочена: одно напоминание на каждый срок (если срок перенесут — придёт снова)
                if ($this->alreadySent('klient_task_overdue', $link, $deadline)) {
                    continue;
                }

                SiteNotificationService::send(
                    [$task->responsible_id], 'klient', 'klient_task_overdue', $task->title,
                    "Просрочена задача ({$task->type}) по клиенту «{$klientName}», срок был " . $deadline->format('d.m.Y H:i'),
                    $link, null
                );

                continue;
            }

            if ($this->alreadySent('klient_task_due_today', $link, $now->copy()->startOfDay())) {
                continue;
            }

            SiteNotificationService::send(
                [$task->responsible_id], 'klient', 'klient_task_due_today', $task->title,
                "Сегодня в {$deadline->format('H:i')} срок задачи ({$task->type}) по клиенту «{$klientName}»",
                $link, null
            );
        }
    }

    private function remindDeals(Carbon $now): void
    {
        $deals = KlientDeal::query()
            ->whereNotIn('status', self::CLOSED_DEAL_STATUSES)
            ->whereNotNull('deadline')
            ->where('deadline', '<=', $now->copy()->addDay()->endOfDay())
            ->get();

        foreach ($deals as $deal) {
            $deadline = Carbon::parse($deal->deadline);
            $link = "/klient-deals/{$deal->id}";
            $responsibleIds = $deal->responsibles()->pluck('users.id')->all();

            if (empty($responsibleIds)) {
                continue;
            }

            // Срок сделки — дата (время обычно 00:00), просрочка — со следующего дня
            if ($deadline->copy()->endOfDay()->lt($now)) {
                if ($this->alreadySent('klient_deal_overdue', $link, $deadline)) {
                    continue;
                }

                SiteNotificationService::send(
                    $responsibleIds, 'klient', 'klient_deal_overdue', $deal->name,
                    'Сделка просрочена, срок был ' . $deadline->format('d.m.Y'),
                    $link, null
                );

                continue;
            }

            // Срок завтра (сегодняшний срок считаем уже «скоро» и тоже напоминаем один раз)
            if ($this->alreadySent('klient_deal_due_soon', $link, $deadline->copy()->subDays(2))) {
                continue;
            }

            SiteNotificationService::send(
                $responsibleIds, 'klient', 'klient_deal_due_soon', $deal->name,
                ($deadline->isToday() ? 'Срок сделки сегодня, ' : 'Срок сделки завтра, ') . $deadline->format('d.m.Y'),
                $link, null
            );
        }
    }

    private function alreadySent(string $type, string $link, Carbon $since): bool
    {
        return SiteNotification::where('type', $type)
            ->where('link', $link)
            ->where('created_at', '>=', $since)
            ->exists();
    }
}
