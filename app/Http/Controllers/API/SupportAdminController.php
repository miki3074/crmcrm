<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportThread;
use App\Models\SupportMessagetwo;
use Illuminate\Http\Request;
use App\Services\TelegramService;

class SupportAdminController extends Controller
{
    // список всех тикетов
    public function threads(Request $request)
    {
        $query = SupportThread::with([
            'user:id,name',
            'messages' => fn($q) => $q->latest()->limit(1)
        ])->orderByDesc('updated_at');

        // фильтры
        if ($request->status && $request->status != 'all') {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($qq) use ($request) {
                      $qq->where('name', 'like', "%{$request->search}%");
                  });
            });
        }

        return $query->get();
    }

    // просмотр тикета
    public function show(SupportThread $thread)
    {
        return $thread->load([
            'user:id,name',
            'messages.user:id,name',
            'messages.attachments',
        ]);
    }

    // ответ саппорта
    public function sendMessage(SupportThread $thread, Request $request)
    {
        $data = $request->validate([
            'message' => 'nullable|string',
            'files.*' => 'nullable|file|max:20480'
        ]);

        // Сохраняем сообщение
        $msg = $thread->messages()->create([
            'user_id'    => auth()->id(),
            'body'       => $data['message'] ?? null,
            'is_support' => true,
        ]);

        // Сохраняем файлы
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('support', 'public');

                $msg->attachments()->create([
                    'path'          => $path,
                    'mime_type'     => $file->getMimeType(),
                    'original_name' => $file->getClientOriginalName(),
                    'size'          => $file->getSize(),
                ]);
            }
        }

        // ========================================================
        // НАЧАЛО: Отправка уведомления в Telegram
        // ========================================================

        // Получаем владельца тикета (пользователя)
        $client = $thread->user;

        // Проверяем, есть ли пользователь и заполнен ли у него telegram_chat_id
        // (Предполагается, что поле в БД называется telegram_chat_id)
        if ($client && $client->telegram_chat_id) {

            // Формируем текст ответа
            // htmlspecialchars нужен, чтобы спецсимволы в сообщении не сломали HTML-разметку Телеграма
            $replyBody = !empty($data['message'])
                ? htmlspecialchars($data['message'])
                : '<i>(Отправлен файл)</i>';

            $text = "🔔 <b>Ответ от техподдержки</b>\n";
            $text .= "Тикет: #{$thread->id} - " . htmlspecialchars($thread->subject) . "\n\n";
            $text .= "💬 <b>Ответ:</b>\n{$replyBody}";

            // Отправляем через ваш сервис
            TelegramService::sendMessage($client->telegram_chat_id, $text);
        }
        // ========================================================
        // КОНЕЦ: Отправка уведомления
        // ========================================================

        $thread->touch();

        return $msg->load('user:id,name', 'attachments');
    }

    // закрыть тикет
    public function close(SupportThread $thread)
    {
        $thread->update(['status' => 'closed']);
        return response()->json(['message' => 'Тикет закрыт']);
    }

    // открыть тикет
    public function reopen(SupportThread $thread)
    {
        $thread->update(['status' => 'open']);
        return response()->json(['message' => 'Тикет открыт']);
    }
}
