<?php

namespace App\Notify;

/**
 * Точка входа универсальных уведомлений.
 *
 *     Notify::type('task.file_added')
 *         ->to($userIds)                 // id или коллекция id
 *         ->title($task->title)          // заголовок (название сущности)
 *         ->message('Добавлен файл')     // текст
 *         ->link("/tasks/{$task->id}")   // куда вести по клику (необязательно)
 *         ->from(auth()->id())           // автор действия — ему не отправляется
 *         ->except([$someId])            // кого ещё исключить (необязательно)
 *         ->collapse()                   // заменить своё же непрочитанное (для чата)
 *         ->send();
 *
 * Каналы (сайт, push, Telegram, почта) выбираются автоматически
 * по настройкам каждого получателя — см. config/notify.php.
 */
class Notify
{
    public static function type(string $type): PendingNotification
    {
        return new PendingNotification($type);
    }
}
