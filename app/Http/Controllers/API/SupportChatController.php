<?php

// app/Http/Controllers/API/SupportChatController.php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SupportThread;
use App\Models\SupportMessagetwo;
use App\Models\SupportAttachmenttwo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            ->orderByDesc('updated_at')
            ->get();

        return $threads;
    }

    // создать новый диалог (первое обращение)
    public function createThread(Request $request)
    {
        $data = $request->validate([
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'files.*' => 'nullable|file|max:20480', // 20 МБ
        ]);

        $user = $request->user();

        $thread = SupportThread::create([
            'user_id' => $user->id,
            'subject' => $data['subject'] ?? null,
            'status'  => 'open',
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

        return $thread->load('messages.attachments', 'user:id,name');
    }

    // сообщения в диалоге
    public function messages(SupportThread $thread, Request $request)
    {
        // можно добавить проверку, что это владелец или саппорт
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

        $msg = $thread->messages()->create([
            'user_id'    => $user->id,
            'body'       => $data['message'] ?? null,
            'is_support' => $user->hasRole('support') ?? false, // если используешь spatie
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

        $thread->touch(); // обновим updated_at

        return $msg->load('user:id,name', 'attachments');
    }
}

