<?php

namespace App\Services;

use App\Models\Klient;
use App\Models\KlientDeal;
use App\Models\MediaPlan;
use App\Models\Subtask;
use App\Models\Task;
use App\Notify\Notify;
use Illuminate\Support\Facades\DB;

/**
 * Помощники для уведомлений по задачам, подзадачам и клиентам:
 * списки участников, ссылки, типовые сообщения.
 *
 * Сама отправка идёт через универсальный App\Notify\Notify (сайт, push,
 * Telegram, почта — по настройкам получателя). send/sendCollapsed —
 * короткая форма записи для уже написанного кода.
 *
 * Тот, кто совершил действие ($actorId), уведомление не получает никогда.
 */
class SiteNotificationService
{
    /**
     * Отправить уведомление списку пользователей.
     */
    public static function send(
        iterable $userIds,
        string $category,
        string $type,
        ?string $title,
        string $message,
        ?string $link,
        ?int $actorId,
        array $exclude = []
    ): void {
        Notify::type("{$category}.{$type}")
            ->to($userIds)
            ->title($title)
            ->message($message)
            ->link($link)
            ->from($actorId)
            ->except($exclude)
            ->send();
    }

    /**
     * Для частых событий (сообщения в чате): пока уведомление по этой
     * ссылке не прочитано, новое заменяет его, а не копится.
     */
    public static function sendCollapsed(
        iterable $userIds,
        string $category,
        string $type,
        ?string $title,
        string $message,
        string $link,
        ?int $actorId
    ): void {
        Notify::type("{$category}.{$type}")
            ->to($userIds)
            ->title($title)
            ->message($message)
            ->link($link)
            ->from($actorId)
            ->collapse()
            ->send();
    }

    /**
     * Все участники задачи: исполнители, ответственные, наблюдатели, создатель.
     */
    public static function taskParticipantIds(Task $task): array
    {
        return collect()
            ->merge($task->executors()->pluck('users.id'))
            ->merge($task->responsibles()->pluck('users.id'))
            ->merge($task->watcherstask()->pluck('users.id'))
            ->push($task->creator_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Все участники подзадачи: исполнители, ответственные, создатель.
     */
    public static function subtaskParticipantIds(Subtask $subtask): array
    {
        return collect()
            ->merge($subtask->executors()->pluck('users.id'))
            ->merge($subtask->responsibles()->pluck('users.id'))
            ->push($subtask->creator_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Команда клиента: создатель + пользователи с доступом (klient_access).
     */
    public static function klientTeamIds(Klient $klient): array
    {
        return collect()
            ->merge($klient->allowedUsers()->pluck('users.id'))
            ->push($klient->user_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Участники сделки: создатель + ответственные.
     */
    public static function klientDealParticipantIds(KlientDeal $deal): array
    {
        return collect()
            ->merge($deal->responsibles()->pluck('users.id'))
            ->push($deal->creator_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Ответственные по всем строкам медиаплана.
     */
    public static function mediaPlanResponsibleIds(MediaPlan $mediaPlan): array
    {
        return DB::table('media_plan_item_user')
            ->whereIn('media_plan_item_id', $mediaPlan->items()->pluck('id'))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function taskLink(Task $task): string
    {
        return "/tasks/{$task->id}";
    }

    public static function subtaskLink(Subtask $subtask): string
    {
        return "/subtasks/{$subtask->id}";
    }

    /**
     * Добавление участников в задачу/подзадачу: добавленным — личное
     * сообщение, остальным участникам — общее.
     */
    public static function membersAdded(
        string $category,
        array $participantIds,
        array $addedIds,
        string $roleLabel,
        ?string $title,
        ?string $link,
        ?int $actorId
    ): void {
        $addedIds = array_map('intval', $addedIds);
        $entity = $category === 'subtask' ? 'подзадачу' : 'задачу';

        self::send(
            $addedIds,
            $category,
            'member_added',
            $title,
            "Вас добавили в {$entity}: {$roleLabel}",
            $link,
            $actorId
        );

        $names = \App\Models\User::whereIn('id', $addedIds)->pluck('name')->implode(', ');

        if ($names === '') {
            return;
        }

        self::send(
            $participantIds,
            $category,
            'member_added',
            $title,
            "Новый участник ({$roleLabel}): {$names}",
            $link,
            $actorId,
            $addedIds
        );
    }

    /**
     * Исключение из задачи/подзадачи — только исключённому.
     */
    public static function memberRemoved(
        string $category,
        int $removedId,
        string $roleLabel,
        ?string $title,
        ?string $link,
        ?int $actorId
    ): void {
        $entity = $category === 'subtask' ? 'подзадачи' : 'задачи';

        self::send(
            [$removedId],
            $category,
            'member_removed',
            $title,
            "Вас исключили из {$entity} (роль: {$roleLabel})",
            $link,
            $actorId
        );
    }
}
