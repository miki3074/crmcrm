<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use App\Models\SupportReply;
use App\Models\User;

use App\Services\TelegramService;

class AdminSupportController extends Controller
{
  public function index()
{
    $this->authorize('viewAny', SupportMessage::class);

    $supportId = auth()->id();

    // ⬅ ВСЕ сотрудники поддержки (для передачи обращения)
    $supportUsers = User::role('support')
        ->select('id', 'name')
        ->get();

    $messages = SupportMessage::with([
    'user:id,name,email,telegram_chat_id',
    'attachments:id,support_message_id,path,original_name,mime_type',
    'replies.user:id,name',
    'replies.user.roles:id,name',
    'replies.attachment:id,support_reply_id,path,original_name,mime_type' // ⬅ ДОБАВИТЬ
])
->where('status', 'open')
->where('assigned_support_id', $supportId)
->latest()
->paginate(20);

    return inertia('Support/Index', [
        'messages' => $messages,
        'supportUsers' => $supportUsers, // ⬅ передаём список сотрудников
    ]);
}



public function reply(Request $r, SupportMessage $message)
{
    $this->authorize('viewAny', SupportMessage::class);

    $r->validate([
        'reply' => 'nullable|string|max:2000',
        'file'  => 'nullable|file|max:51200' // 50MB
    ]);

    if (!$r->reply && !$r->file) {
        return response()->json(['error' => 'Пустое сообщение'], 422);
    }

    $reply = SupportReply::create([
        'support_message_id' => $message->id,
        'user_id'            => auth()->id(),
        'reply'              => $r->reply
    ]);

    if ($r->hasFile('file')) {
        $path = $r->file('file')->store('support/replies', 'public');

        $reply->attachment()->create([
            'path'          => $path,
            'original_name' => $r->file('file')->getClientOriginalName(),
            'mime_type'     => $r->file('file')->getMimeType()
        ]);
    }

    $reply->load('user.roles', 'attachment');
    return response()->json(['reply' => $reply]);
}





public function close(SupportMessage $message)
{
    $this->authorize('viewAny', SupportMessage::class);

    $message->update(['status' => 'closed']);

    return back()->with('success', 'Обращение завершено.');
}


public function transfer(Request $r, $id)
{
    $user = auth()->user();

    // Проверка, может ли пользователь передавать (роль support)
    if (!$user->hasRole('support')) {
        return response()->json(['error' => 'Нет доступа'], 403);
    }

    $data = $r->validate([
        'new_support_id' => 'required|exists:users,id',
    ]);

    $message = SupportMessage::findOrFail($id);

    // Проверка — можно ли менять
    if ($message->status === 'closed') {
        return response()->json(['error' => 'Обращение уже закрыто'], 400);
    }

    // Проверяем, что назначаемый пользователь — сотрудник техподдержки
    $newSupport = User::find($data['new_support_id']);
    if (!$newSupport->hasRole('support')) {
        return response()->json(['error' => 'Пользователь не из техподдержки'], 422);
    }

    // Меняем назначенного сотрудника
    $message->assigned_support_id = $newSupport->id;
    $message->save();

    // (опционально) лог передачи
    // SupportTransferLog::create([...]);

    return response()->json([
        'message' => 'Обращение передано',
        'assigned_to' => $newSupport->name
    ]);
}




}
