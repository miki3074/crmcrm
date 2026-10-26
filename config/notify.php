<?php

/*
|--------------------------------------------------------------------------
| Универсальные уведомления
|--------------------------------------------------------------------------
|
| Отправка из любого места:
|
|     Notify::type('task.file_added')
|         ->to($userIds)
|         ->title($task->title)
|         ->message('Добавлен файл «отчёт.pdf»')
|         ->link("/tasks/{$task->id}")
|         ->from(auth()->id())
|         ->send();
|
| Тип — «категория.событие». Новый тип можно отправлять сразу, без
| регистрации: он получит каналы по умолчанию. Чтобы тип появился на
| странице настроек пользователя, добавьте его в 'types' ниже.
|
*/

return [

    /*
     * Каналы доставки и включены ли они по умолчанию.
     * Telegram и почта по умолчанию выключены: часть событий уже шлёт их
     * напрямую из контроллеров, иначе пришли бы дубли. При переводе старых
     * вызовов на Notify можно включить канал для конкретного типа через
     * 'defaults' в описании типа.
     */
    'channels' => [
        'site' => ['label' => 'На сайте', 'default' => true],
        'push' => ['label' => 'Push (ПК и телефон)', 'default' => true],
        'telegram' => ['label' => 'Telegram', 'default' => false],
        'mail' => ['label' => 'Почта', 'default' => false],
    ],

    'categories' => [
        'task' => 'Задачи',
        'subtask' => 'Подзадачи',
        'chat' => 'Чат',
        'meeting' => 'Совещания',
        'klient' => 'Клиенты',
        'system' => 'Система',
    ],

    /*
     * Реестр типов для страницы настроек.
     * 'defaults' переопределяет каналы по умолчанию для конкретного типа.
     */
    'types' => [
        // Задачи
        'task.file_added' => ['label' => 'Загружены файлы'],
        'task.result_added' => ['label' => 'Добавлен результат выполнения'],
        'task.subtask_created' => ['label' => 'Создана подзадача, где вы участник'],
        'task.checklist_item_added' => ['label' => 'Пункт чек-листа'],
        'task.mentioned' => ['label' => 'Вас отметили в комментарии'],
        'task.comment_added' => ['label' => 'Новый комментарий'],
        'task.file_review_assigned' => ['label' => 'Файл на согласование'],
        'task.file_rejected' => ['label' => 'Файл возвращён с замечаниями'],
        'task.file_updated' => ['label' => 'Файл обновлён после замечаний'],
        'task.member_added' => ['label' => 'Новый участник / вас добавили'],
        'task.member_removed' => ['label' => 'Вас исключили'],
        'task.task_deleted' => ['label' => 'Задача удалена'],
        'task.task_completed' => ['label' => 'Задача завершена'],

        // Подзадачи
        'subtask.file_added' => ['label' => 'Загружены файлы'],
        'subtask.comment_added' => ['label' => 'Новый комментарий'],
        'subtask.mentioned' => ['label' => 'Вас отметили в комментарии'],
        'subtask.checklist_item_added' => ['label' => 'Пункт чек-листа'],
        'subtask.member_added' => ['label' => 'Новый участник / вас добавили'],
        'subtask.subtask_child_created' => ['label' => 'Создана вложенная подзадача'],
        'subtask.member_removed' => ['label' => 'Вас исключили'],
        'subtask.subtask_deleted' => ['label' => 'Подзадача удалена'],
        'subtask.subtask_completed' => ['label' => 'Подзадача завершена'],

        // Чат
        'chat.chat_message' => ['label' => 'Личное сообщение'],
        'chat.chat_group_message' => ['label' => 'Сообщение в группе'],
        'chat.chat_group_created' => ['label' => 'Вас добавили в новую группу'],
        'chat.chat_group_member_added' => ['label' => 'Вас добавили в группу'],

        // Совещания
        'meeting.meeting_created' => ['label' => 'Приглашение на совещание'],
        'meeting.agenda_remark' => ['label' => 'Замечание к повестке'],

        // Клиенты
        'klient.klient_access_granted' => ['label' => 'Открыт доступ к клиенту'],
        'klient.klient_access_revoked' => ['label' => 'Закрыт доступ к клиенту'],
        'klient.klient_status_changed' => ['label' => 'Изменён статус клиента'],
        'klient.klient_file_added' => ['label' => 'Файл в карточке клиента'],
        'klient.klient_deal_assigned' => ['label' => 'Вы ответственный по сделке'],
        'klient.klient_deal_unassigned' => ['label' => 'Вас сняли со сделки'],
        'klient.klient_deal_status_changed' => ['label' => 'Изменён статус сделки'],
        'klient.klient_deal_closed' => ['label' => 'Сделка закрыта'],
        'klient.klient_deal_file_added' => ['label' => 'Файлы в сделке'],
        'klient.klient_deal_due_soon' => ['label' => 'Скоро срок сделки'],
        'klient.klient_deal_overdue' => ['label' => 'Сделка просрочена'],
        'klient.klient_task_assigned' => ['label' => 'Назначена задача по клиенту'],
        'klient.klient_task_unassigned' => ['label' => 'Задача по клиенту передана другому'],
        'klient.klient_task_completed' => ['label' => 'Задача по клиенту выполнена'],
        'klient.klient_task_cancelled' => ['label' => 'Задача по клиенту отменена'],
        'klient.klient_task_file_added' => ['label' => 'Файлы в задаче по клиенту'],
        'klient.klient_task_due_today' => ['label' => 'Срок задачи по клиенту сегодня'],
        'klient.klient_task_overdue' => ['label' => 'Задача по клиенту просрочена'],
        'klient.media_plan_created' => ['label' => 'Создан медиаплан'],
        'klient.media_plan_item_assigned' => ['label' => 'Вы ответственный в медиаплане'],
        'klient.media_plan_status_changed' => ['label' => 'Изменён статус медиаплана'],
        'klient.media_plan_deleted' => ['label' => 'Медиаплан удалён'],

        // Система
        'system.test' => ['label' => 'Тестовое уведомление', 'hidden' => true],
    ],

    'webpush' => [
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
        // mailto: или https-адрес владельца — требование протокола Web Push
        'subject' => env('VAPID_SUBJECT') ?: 'mailto:' . env('MAIL_FROM_ADDRESS', 'admin@example.com'),
        // Windows/XAMPP: PHP не может шифровать push без переменной окружения
        // OPENSSL_CONF (путь к openssl.cnf), заданной ДО запуска Apache/PHP.
        // На Linux-сервере обычно не нужно. Проверка: страница настроек уведомлений.
        // Сколько секунд push-сервис хранит сообщение, если устройство офлайн
        'ttl' => 86400,
        'icon' => '/icons/icon-192.png',
    ],

];
