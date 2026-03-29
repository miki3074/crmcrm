<?php

// app/Http/Controllers/API/SupportChatController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportThread;
use App\Models\SupportMessagetwo;
use App\Models\SupportAttachmenttwo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Support\Str;

class SupportChatController extends Controller
{
    // список диалогов пользователя
    public function threads(Request $request)
    {
        $user = $request->user();

        $threads = SupportThread::with([
            'user:id,name',
            'messages' => fn($q) => $q->latest()->limit(1),
        ])
            ->where('user_id', $user->id)
            // Добавляем подсчет непрочитанных сообщений от саппорта
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('is_support', true)->whereNull('read_at');
            }])
            ->orderByDesc('updated_at')
            ->get();

        return $threads;
    }

    // создать новый диалог (первое обращение)
    // создать новый диалог (первое обращение)
    public function createThread(Request $request)
    {
        $data = $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'files.*' => 'nullable|file|max:20480',
        ]);

        $user = $request->user();

        // ---------------------------------------------------------
        // ЛОГИКА ФОРМИРОВАНИЯ ТЕМЫ
        // ---------------------------------------------------------
        $subject = $data['subject'] ?? null;

        // Если темы нет, берем из сообщения
        if (empty($subject) && !empty($data['message'])) {
            // Обрезаем сообщение до 50 символов и добавляем "..."
            $subject = Str::limit($data['message'], 50, '...');
        }

        // Если и сообщения не было (только файлы), ставим заглушку
        if (empty($subject)) {
            $subject = 'Новое обращение';
        }

        // ---------------------------------------------------------
        // АЛГОРИТМ РАСПРЕДЕЛЕНИЯ
        // ---------------------------------------------------------
        $assignedAgent = User::whereHas('roles', function($q) {
            $q->where('name', 'support');
        })
            ->withCount(['supportThreads as open_tickets_count' => function ($query) {
                $query->where('status', 'open');
            }])
            ->orderBy('open_tickets_count', 'asc')
            ->first();

        $agentId = $assignedAgent ? $assignedAgent->id : null;

        // Создаем тикет с вычисленной темой ($subject)
        $thread = SupportThread::create([
            'user_id'         => $user->id,
            'support_user_id' => $agentId,
            'subject'         => $subject, // <--- ИСПОЛЬЗУЕМ СФОРМИРОВАННУЮ ТЕМУ
            'status'          => 'open',
        ]);

        $message = $thread->messages()->create([
            'user_id'    => $user->id,
            'body'       => $data['message'] ?? null,
            'is_support' => false,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('support', 'public');
                SupportAttachmenttwo::create([
                    'message_id'    => $message->id,
                    'path'          => $path,
                    'mime_type'     => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'size'          => (int) round($file->getSize() / 1024),
                ]);
            }
        }

        // ---------------------------------------------------------
        // УВЕДОМЛЕНИЕ В TELEGRAM
        // ---------------------------------------------------------

        // Используем $thread->subject, так как там уже лежит финальная тема
        $safeSubject = htmlspecialchars($thread->subject);
        $safeUser    = htmlspecialchars($user->name);

        if ($assignedAgent && $assignedAgent->telegram_chat_id) {
            $text = "🆕 <b>Вам назначен новый тикет #{$thread->id}</b>\n";
            $text .= "Клиент: {$safeUser}\n";
            $text .= "Тема: {$safeSubject}"; // <--- Теперь тут тема из сообщения

            TelegramService::sendMessage($assignedAgent->telegram_chat_id, $text);
        }
        elseif (config('services.telegram.support_chat_id')) {
            $text = "🆘 <b>Новый тикет #{$thread->id} (Никому не назначен)</b>\n";
            $text .= "Клиент: {$safeUser}\n";
            $text .= "Тема: {$safeSubject}";

            TelegramService::sendMessage(config('services.telegram.support_chat_id'), $text);
        }

        return $thread->load('messages.attachments', 'user:id,name', 'supportAgent:id,name');
    }

    // сообщения в диалоге
    public function messages(SupportThread $thread, Request $request)
    {
        $user = $request->user();

        // ПРОВЕРКА ДОСТУПА (опционально, но желательно)
        if ($thread->user_id !== $user->id && !$user->hasRole('support')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // ЛОГИКА ОБНОВЛЕНИЯ ПРОЧТЕНИЯ
        $unreadQuery = $thread->messages()->whereNull('read_at');

        if ($user->hasRole('support')) {
            // Если зашел саппорт — помечаем как прочитанные сообщения клиента
            $unreadQuery->where('is_support', false)->update(['read_at' => now()]);
        } else {
            // Если зашел клиент — помечаем как прочитанные сообщения саппорта
            $unreadQuery->where('is_support', true)->update(['read_at' => now()]);
        }

        // Загружаем данные для возврата
        $thread->load([
            'user:id,name',
            'messages.user:id,name',
            'messages.attachments',
        ]);

        return $thread;
    }

// отправка сообщения в существующий диалог
    public function sendMessage(SupportThread $thread, Request $request)
    {
        $data = $request->validate([
            'message' => 'nullable|string',
            'files.*' => 'nullable|file|max:20480',
        ]);

        $user = $request->user();

        // Определяем роль (поддержка или клиент)
        // Если используете Spatie permission:
        $isSupport = $user->hasRole('support');
        // Если поле в БД: $isSupport = $user->is_support;

        $msg = $thread->messages()->create([
            'user_id'    => $user->id,
            'body'       => $data['message'] ?? null,
            'is_support' => $isSupport,
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('support', 'public');
                SupportAttachmenttwo::create([
                    'message_id'    => $msg->id,
                    'path'          => $path,
                    'mime_type'     => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'size'          => (int) round($file->getSize() / 1024),
                ]);
            }
        }

        $thread->touch(); // обновляем дату последнего изменения

        // =================================================================
        // ЛОГИКА УВЕДОМЛЕНИЙ В TELEGRAM
        // =================================================================

        // Подготовка данных для текста
        $safeBody = !empty($data['message'])
            ? htmlspecialchars($data['message'])
            : '<i>(Отправлен файл)</i>';

        $ticketLink = "#{$thread->id}"; // Тут можно добавить ссылку на админку/сайт

        // СЦЕНАРИЙ 1: Пишет СОТРУДНИК ПОДДЕРЖКИ -> Уведомляем КЛИЕНТА
        if ($isSupport) {
            $client = $thread->user; // Владелец тикета

            if ($client && $client->telegram_chat_id) {
                $text = "🔔 <b>Новый ответ поддержки</b> (Тикет {$ticketLink})\n\n";
                $text .= "{$safeBody}";

                TelegramService::sendMessage($client->telegram_chat_id, $text);
            }
        }

        // СЦЕНАРИЙ 2: Пишет КЛИЕНТ -> Уведомляем СОТРУДНИКА
        else {
            $assignedAgent = $thread->supportAgent; // Сотрудник, назначенный на тикет
            $safeUserName  = htmlspecialchars($user->name);

            // А. Если у тикета УЖЕ есть ответственный сотрудник
            if ($assignedAgent && $assignedAgent->telegram_chat_id) {
                $text = "📩 <b>Новое сообщение от клиента</b> (Тикет {$ticketLink})\n";
                $text .= "👤 Клиент: <b>{$safeUserName}</b>\n\n";
                $text .= "{$safeBody}";

                TelegramService::sendMessage($assignedAgent->telegram_chat_id, $text);
            }

            // Б. Если ответственного НЕТ или у него нет Telegram -> Шлем в общий чат
            elseif (config('services.telegram.support_chat_id')) {
                // Если агент есть, но нет ТГ, пометим это
                $agentInfo = $assignedAgent ? " (Назначен: {$assignedAgent->name})" : " (Не назначен)";

                $text = "📩 <b>Сообщение в тикете {$ticketLink}</b>{$agentInfo}\n";
                $text .= "👤 Клиент: <b>{$safeUserName}</b>\n\n";
                $text .= "{$safeBody}";

                TelegramService::sendMessage(config('services.telegram.support_chat_id'), $text);
            }
        }

        return $msg->load('user:id,name', 'attachments');
    }
}

