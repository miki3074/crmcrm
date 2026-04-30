<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SendCommentNotification;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskCommentController extends Controller
{
    /**
     * Получение списка комментариев к задаче.
     */
    public function index(Task $task)
    {
        $this->authorize('view', $task);

        $comments = $task->comments()
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

        $data = $request->validate([
            'body'      => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:task_comments,id',
        ]);

        $comment = TaskComment::create([
            'task_id'   => $task->id,
            'user_id'   => $request->user()->id,
            'body'      => $data['body'],
            'parent_id' => $data['parent_id'] ?? null,
        ]);

        $comment->load(['user:id,name', 'parent.user:id,name']);

        // Отправляем уведомления (Telegram + Email)
        $this->sendNotifications($task, $comment);

        return response()->json($comment, 201);
    }

    /**
     * Обработка и отправка уведомлений о новом комментарии.
     */
    private function sendNotifications(Task $task, TaskComment $comment): void
    {
        $authorId = $comment->user_id;
        $taskUrl = url("/tasks/{$task->id}");

        // Коллекция для хранения ID уже уведомленных пользователей
        $notifiedUserIds = collect([$authorId]);

        // 1. Уведомление об ОТВЕТЕ
        if ($comment->parent && $comment->parent->user_id !== $authorId) {
            $parentAuthor = $comment->parent->user;
            if ($parentAuthor) {
                // Telegram
                if ($parentAuthor->telegram_chat_id) {
                    TelegramService::sendMessage(
                        $parentAuthor->telegram_chat_id,
                        "↩️ <b>Вам ответили</b> в задаче: <b>{$task->title}</b>\n" .
                        "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n" .
                        "<b>{$comment->user->name}:</b>\n{$comment->body}"
                    );
                }

                // Email через Job
                SendCommentNotification::dispatch($task, $comment, $parentAuthor->id, 'reply');
                $notifiedUserIds->push($parentAuthor->id);
            }
        }

        // 2. Уведомление об УПОМИНАНИЯХ
        preg_match_all('/@([\p{L}_]+)/u', $comment->body, $matches);
        $usernames = array_map(fn($u) => str_replace('_', ' ', $u), $matches[1]);

        if (!empty($usernames)) {
            $mentionedUsers = User::whereIn('name', $usernames)
                ->whereNotIn('id', $notifiedUserIds)
                ->get();

            foreach ($mentionedUsers as $mentioned) {
                // Telegram
                if ($mentioned->telegram_chat_id) {
                    TelegramService::sendMessage(
                        $mentioned->telegram_chat_id,
                        "📣 <b>Вас упомянули</b> в задаче: <b>{$task->title}</b>\n" .
                        "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n" .
                        "<b>{$comment->user->name}:</b>\n{$comment->body}"
                    );
                }

                // Email через Job
                SendCommentNotification::dispatch($task, $comment, $mentioned->id, 'mention');
                $notifiedUserIds->push($mentioned->id);
            }

            // Если были упоминания, прекращаем дальнейшую рассылку
            return;
        }

        // 3. Уведомление ВСЕХ ОСТАЛЬНЫХ участников (если не было ответа и упоминаний)
        if ($comment->parent_id === null) {
            $participants = collect([]);
            $participants = $participants->merge(DB::table('task_responsibles')->where('task_id', $task->id)->pluck('user_id'));
            $participants = $participants->merge(DB::table('task_executors')->where('task_id', $task->id)->pluck('user_id'));
            $participants = $participants->merge(DB::table('task_user_watchers')->where('task_id', $task->id)->pluck('user_id'));

            // Получаем уникальные ID пользователей, которых еще не уведомили
            $participantIds = $participants->unique()->diff($notifiedUserIds);

            foreach ($participantIds as $participantId) {
                $user = User::find($participantId);

                if ($user) {
                    // Telegram
                    if ($user->telegram_chat_id) {
                        TelegramService::sendMessage(
                            $user->telegram_chat_id,
                            "💬 Новое сообщение в задаче: <b>{$task->title}</b>\n" .
                            "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>\n\n" .
                            "<b>{$comment->user->name}:</b>\n{$comment->body}"
                        );
                    }

                    // Email через Job
                    SendCommentNotification::dispatch($task, $comment, $participantId, 'new');
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
