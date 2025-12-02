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
        preg_match_all('/@([\p{L}_]+)/u', $comment->body, $matches);

        $usernames = array_map(fn($u) => str_replace('_', ' ', $u), $matches[1]);

        // Если есть упоминания, обрабатываем только их
        if (!empty($usernames)) {

            $mentionedUsers = \App\Models\User::whereIn('name', $usernames)->get();

            foreach ($mentionedUsers as $mentioned) {
                if ($mentioned->telegram_chat_id) {
                    $taskUrl = url("/tasks/{$task->id}");

                    \App\Services\TelegramService::sendMessage(
                        $mentioned->telegram_chat_id,
                        "📣 Вас упомянули в задаче: <b>{$task->title}</b>\n".
                        "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n".
                        "Автор: {$comment->user->name}\n".
                        "Сообщение:\n{$comment->body}"
                    );
                }
            }

        } else {
            // ======== УВЕДОМЛЕНИЕ ВСЕМ УЧАСТНИКАМ ЗАДАЧИ ========

            // получаем id всех участников задачи
            $participants = collect([]);

            // Ответственные
            $participants = $participants->merge(
                \DB::table('task_responsibles')->where('task_id', $task->id)->pluck('user_id')
            );

            // Исполнители
            $participants = $participants->merge(
                \DB::table('task_executors')->where('task_id', $task->id)->pluck('user_id')
            );

            // Наблюдатели
            $participants = $participants->merge(
                \DB::table('task_user_watchers')->where('task_id', $task->id)->pluck('user_id')
            );

            // Уникальные ID
            $participants = $participants->unique();

            // исключаем автора
            $participants = $participants->reject(fn($id) => $id == $comment->user_id);

            // загружаем пользователей
            $users = \App\Models\User::whereIn('id', $participants)->get();

            // отправляем каждому
            foreach ($users as $user) {
                if ($user->telegram_chat_id) {
                    $taskUrl = url("/tasks/{$task->id}");

                    \App\Services\TelegramService::sendMessage(
                        $user->telegram_chat_id,
                        "💬 Новое сообщение в задаче: <b>{$task->title}</b>\n".
                        "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n".
                        "Автор: {$comment->user->name}\n".
                        "Сообщение:\n{$comment->body}"
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
