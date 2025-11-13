<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMessage;
use App\Models\User;
use App\Models\SupportAttachment;

use App\Services\TelegramService;


class SupportController extends Controller
{
    public function store(Request $r)
    {
        $user = auth()->user();

       $data = $r->validate(
    [
        'message'   => 'required|string|max:2000',
        'page_url'  => 'nullable|string|max:255',

        'files.*'   => 'file|max:20480|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,webm',
    ],
    [
        'files.*.mimes' => 'Каждый файл должен быть одного из следующих форматов: JPG, JPEG, PNG, GIF, WEBP, MP4, MOV, AVI, WEBM.',
        'files.*.file'  => 'Каждый прикреплённый объект должен быть файлом.',
        'files.*.max'   => 'Размер каждого файла не должен превышать 20 МБ.',
    ]
);


        // 1. Все support-пользователи
        $supportUsers = User::role('support')->get();

        if ($supportUsers->isEmpty()) {
            return response()->json(['error' => 'Нет сотрудников техподдержки'], 500);
        }

        // 2. Считаем открытые тикеты и выбираем самого свободного
        $supportUser = $supportUsers
            ->map(function ($u) {
                $u->open_tickets = SupportMessage::where('assigned_support_id', $u->id)
                    ->where('status', 'open')
                    ->count();
                return $u;
            })
            ->sortBy('open_tickets')
            ->first();

        // 3. Создаём сообщение
        $message = SupportMessage::create([
            'user_id'            => $user->id,
            'assigned_support_id'=> $supportUser->id,
            'message'            => $data['message'],
            'page_url'           => $data['page_url'] ?? null,
            'email'              => $user->email,
            'telegram_chat_id'   => $user->telegram_chat_id,
            'status'             => 'open',
        ]);



        // 4. Сохраняем файлы, если есть
        if ($r->hasFile('files')) {
            foreach ($r->file('files') as $file) {
                $path = $file->store('support', 'public'); // storage/app/public/support

                SupportAttachment::create([
                    'support_message_id' => $message->id,
                    'path'               => $path,
                    'original_name'      => $file->getClientOriginalName(),
                    'mime_type'          => $file->getMimeType(),
                    'size'               => $file->getSize(),
                ]);
            }
        }

if (!empty($supportUser->telegram_chat_id)) {
    TelegramService::sendMessage(
        $supportUser->telegram_chat_id,
        "🆕 Новое обращение №{$message->id}\n"
        . "От: <b>{$user->name}</b>\n"
        . "Email: {$user->email}\n\n"
        . "<b>Сообщение:</b>\n"
        . "{$message->message}\n\n"
        . (!empty($message->page_url) ? "📎 Страница: {$message->page_url}" : "")
    );
}

        return response()->json([
            'message'     => 'Сообщение отправлено',
            'assigned_to' => $supportUser->name,
            'support_id'  => $supportUser->id,
        ], 201);
    }
}
