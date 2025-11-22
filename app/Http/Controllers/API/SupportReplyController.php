<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SupportMessage;
use App\Models\SupportReply;
use Illuminate\Support\Facades\Auth;
use App\Models\SupportReplyAttachment;

use App\Models\User;

use App\Services\TelegramService;

class SupportReplyController extends Controller
{
    /**
     * Ответ пользователя
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
        'support_message_id' => 'required|exists:support_messages,id',
        'reply' => 'nullable|string|max:1000',
        'file'  => 'nullable|file|max:20480', // до 20MB
    ]);

    $message = SupportMessage::findOrFail($validated['support_message_id']);

    if ($message->user_id !== Auth::id()) {
        return response()->json(['message' => 'Доступ запрещён.'], 403);
    }

    $reply = SupportReply::create([
        'support_message_id' => $message->id,
        'user_id' => Auth::id(),
        'reply' => $validated['reply'] ?? '',
    ]);

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $path = $file->store('support/replies', 'public');

        $attachment = SupportReplyAttachment::create([
            'support_reply_id' => $reply->id,
            'path'            => $path,
            'original_name'   => $file->getClientOriginalName(),
            'mime_type'       => $file->getMimeType(),
            'size'            => $file->getSize(),
        ]);

        $reply->setRelation('attachment', $attachment);
    }

    $message->update(['status' => 'open']);


        // Обновим статус
        $message->update(['status' => 'open']);


        if (!empty($message->telegram_chat_id)) {

        $text =
            "🛠 <b>Ответ от пользователя</b>\n\n" .
            "<b>Обращение №{$message->id}</b>\n\n" .
            "<b>Ответ:</b>\n" .
            "{$reply->reply}\n";

        

        TelegramService::sendMessage($message->telegram_chat_id, $text);
    }


        return response()->json([
            'success' => true,
            'reply' => $reply->load('user:id,name'),
        ]);
    }

    /**
     * Ответ техподдержки
     */
    public function storeSupport(Request $request)
    {
        $validated = $request->validate([
            'support_message_id' => 'required|exists:support_messages,id',
            'reply' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        if (!$user->hasRole('support')) {
            return response()->json(['message' => 'Нет прав доступа.'], 403);
        }

        $message = SupportMessage::findOrFail($validated['support_message_id']);

        $reply = SupportReply::create([
            'support_message_id' => $message->id,
            'user_id' => $user->id,
            'reply' => $validated['reply'],
        ]);

        // Меняем статус на “ожидает ответа пользователя”
        $message->update(['status' => 'answered']);

        // 💬 при желании можно тут отправить уведомление пользователю в Telegram

        return response()->json([
            'success' => true,
            'reply' => $reply->load('user:id,name'),
        ]);
    }
}
