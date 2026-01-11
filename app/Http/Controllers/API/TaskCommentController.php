<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User; // Добавим для удобства
use Illuminate\Support\Facades\DB; // Добавим для удобства

class TaskCommentController extends Controller
{
    /**
     * Получение списка комментариев к задаче.
     */
    public function index(Task $task)
    {
        $this->authorize('view', $task);

        $comments = $task->comments()
            // [ИЗМЕНЕНО] Загружаем автора комментария И, если это ответ, родительский коммент и его автора.
            ->with(['user:id,name', 'parent.user:id,name'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($comments);
    }

    /**
     * Сохранение нового комментария.
     */
    public function store(Request $request, Task $task)
    {
        $this->authorize('comment', $task);

        // [ИЗМЕНЕНО] Добавлена валидация для parent_id
        $data = $request->validate([
            'body'      => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:task_comments,id',
        ]);

        $comment = TaskComment::create([
            'task_id'   => $task->id,
            'user_id'   => $request->user()->id,
            'body'      => $data['body'],
            'parent_id' => $data['parent_id'] ?? null, // Сохраняем ID родителя
        ]);

        // Загружаем связи для ответа API и для логики уведомлений
        $comment->load(['user:id,name', 'parent.user:id,name']);


        // [ИЗМЕНЕНО] Улучшенная логика уведомлений
        $this->sendNotifications($task, $comment);


        return response()->json($comment, 201);
    }

    /**
     * Обработка и отправка уведомлений о новом комментарии.
     * Вынесено в отдельный метод для чистоты кода.
     */
    private function sendNotifications(Task $task, TaskComment $comment): void
    {
        $authorId = $comment->user_id;
        $taskUrl = url("/tasks/{$task->id}");

        // Коллекция для хранения ID уже уведомленных пользователей, чтобы не спамить
        $notifiedUserIds = collect([$authorId]);

        // 1. Приоритет №1: Уведомление об ОТВЕТЕ
        if ($comment->parent && $comment->parent->user_id !== $authorId) {
            $parentAuthor = $comment->parent->user;
            if ($parentAuthor && $parentAuthor->telegram_chat_id) {
                \App\Services\TelegramService::sendMessage(
                    $parentAuthor->telegram_chat_id,
                    "↩️ <b>Вам ответили</b> в задаче: <b>{$task->title}</b>\n".
                    "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n".
                    "<b>{$comment->user->name}:</b>\n{$comment->body}"
                );
                $notifiedUserIds->push($parentAuthor->id); // Добавляем в список, чтобы не уведомить дважды
            }
        }

        // 2. Приоритет №2: Уведомление об УПОМИНАНИЯХ
        preg_match_all('/@([\p{L}_]+)/u', $comment->body, $matches);
        $usernames = array_map(fn($u) => str_replace('_', ' ', $u), $matches[1]);

        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('name', $usernames)
                ->whereNotIn('id', $notifiedUserIds) // Исключаем уже уведомленных
                ->get();

            foreach ($mentionedUsers as $mentioned) {
                if ($mentioned->telegram_chat_id) {
                    \App\Services\TelegramService::sendMessage(
                        $mentioned->telegram_chat_id,
                        "📣 <b>Вас упомянули</b> в задаче: <b>{$task->title}</b>\n".
                        "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n".
                        "<b>{$comment->user->name}:</b>\n{$comment->body}"
                    );
                    $notifiedUserIds->push($mentioned->id);
                }
            }
            // Если были упоминания, прекращаем дальнейшую рассылку остальным.
            // Если вы хотите, чтобы остальные тоже получали уведомления, закомментируйте следующую строку.
            return;
        }

        // 3. Приоритет №3: Уведомление ВСЕХ ОСТАЛЬНЫХ участников (если не было ответа и упоминаний)
        // Этот блок сработает, только если это обычное сообщение в чат
        if ($comment->parent_id === null) {
            $participants = collect([]);
            $participants = $participants->merge(DB::table('task_responsibles')->where('task_id', $task->id)->pluck('user_id'));
            $participants = $participants->merge(DB::table('task_executors')->where('task_id', $task->id)->pluck('user_id'));
            $participants = $participants->merge(DB::table('task_user_watchers')->where('task_id', $task->id)->pluck('user_id'));

            // Получаем уникальные ID пользователей, которых еще не уведомили
            $participantIds = $participants->unique()->diff($notifiedUserIds);

            $usersToNotify = User::whereIn('id', $participantIds)->get();

            foreach ($usersToNotify as $user) {
                if ($user->telegram_chat_id) {
                    \App\Services\TelegramService::sendMessage(
                        $user->telegram_chat_id,
                        "💬 Новое сообщение в задаче: <b>{$task->title}</b>\n".
                        "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n".
                        "<b>{$comment->user->name}:</b>\n{$comment->body}"
                    );
                }
            }
        }
    }


    /**
     * Удаление комментария.
     */
    public function destroy(TaskComment $comment)
    {
        $this->authorize('deleteComment', [Task::class, $comment]);
        $comment->delete();
        return response()->json(['ok' => true]);
    }
}
