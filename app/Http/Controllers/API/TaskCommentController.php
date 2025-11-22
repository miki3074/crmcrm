<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskComment;

class TaskCommentController extends Controller
{
    public function index(Task $task)
    {
        $this->authorize('view', $task);

        $comments = $task->comments()
            ->with(['user:id,name'])
            ->orderBy('created_at','asc')
            ->get();

        return response()->json($comments);
    }

    public function store(Request $request, Task $task)
    {
        $this->authorize('comment', $task);

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'body'    => $data['body'],
        ]);

        // ищем упоминания @username
preg_match_all('/@([A-Za-z0-9_]+)/u', $comment->body, $matches);
$usernames = $matches[1] ?? [];

if (!empty($usernames)) {
    $mentionedUsers = \App\Models\User::whereIn('name', $usernames)->get();

    foreach ($mentionedUsers as $mentioned) {
        if ($mentioned->telegram_chat_id) {
            \App\Services\TelegramService::sendMessage(
                $mentioned->telegram_chat_id,
                "📣 Вас упомянули в задаче: <b>{$task->title}</b>\n".
                "Сообщение: {$comment->body}\n".
                "Автор: {$comment->user->name}"
            );
        }
    }
}


        return response()->json(
            $comment->load('user:id,name'),
            201
        );
    }

    public function destroy(TaskComment $comment)
    {
        // проверяем право удалить
        $this->authorize('deleteComment', [\App\Models\Task::class, $comment]);

        $comment->delete();
        return response()->json(['ok' => true]);
    }



    
}
