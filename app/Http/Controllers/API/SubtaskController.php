<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\SubtaskComment;

use App\Models\SubtaskFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use App\Jobs\SendSubtaskAssignedNotification;


use App\Models\User;

use App\Services\TelegramService;

class SubtaskController extends Controller
{
    public function index(Task $task)
    {
        $this->authorize('view', $task);

       $subtasks = $task->subtasks()
    ->with(['creator:id,name', 'executors:id,name', 'responsibles:id,name'])
    ->get();


        return response()->json($subtasks);
    }

    public function store(Request $request, Task $task)
    {
        $this->authorize('createSubtask', $task);

        // Преобразуем одиночные ID в массивы
        $data = $request->all();
        if (!is_array($data['executor_id'] ?? null)) {
            $data['executor_id'] = [$data['executor_id']];
        }
        if (!is_array($data['responsible_id'] ?? null)) {
            $data['responsible_id'] = [$data['responsible_id']];
        }

        $validated = validator($data, [
            'title'           => 'required|string|max:255',
            'executor_id'     => 'required|array|min:1',
            'executor_id.*'   => 'exists:users,id',
            'responsible_id'  => 'required|array|min:1',
            'responsible_id.*'=> 'exists:users,id',
            'start_date'      => 'required|date',
            'due_date'        => 'required|date|after_or_equal:start_date',
            'parent_id'       => 'nullable|exists:subtasks,id',
        ])->validate();

        $subtask = $task->subtasks()->create([
            'title'      => $validated['title'],
            'start_date' => $validated['start_date'],
            'due_date'   => $validated['due_date'],
            'creator_id' => auth()->id(),
            'parent_id'  => $data['parent_id'] ?? null,
        ]);

        // Привязываем пользователей
        $subtask->executors()->sync($validated['executor_id']);
        $subtask->responsibles()->sync($validated['responsible_id']);

        // ========= УВЕДОМЛЕНИЯ =========
        $recipients = array_unique(array_merge(
            $validated['executor_id'],
            $validated['responsible_id']
        ));

        $taskUrl = url("/tasks/{$task->id}");

        foreach ($recipients as $userId) {
            $user = \App\Models\User::find($userId);

            if (!$user) {
                continue;
            }

            // Определяем роль пользователя
            $role = in_array($userId, $validated['executor_id']) ? 'executor' : 'responsible';
            $roleText = $role === 'executor' ? 'исполнителем' : 'ответственным';

            // 1. TELEGRAM УВЕДОМЛЕНИЕ
            if ($user->telegram_chat_id) {
                \App\Services\TelegramService::sendMessage(
                    $user->telegram_chat_id,
                    "🆕 Вас назначили {$roleText} подзадачи: <b>{$subtask->title}</b>\n" .
                    "📌 Задача: {$task->title}\n" .
                    "📅 Срок: " . date('d.m.Y', strtotime($subtask->due_date)) . "\n" .
                    "🔗 <a href=\"{$taskUrl}\">Открыть задачу</a>"
                );
            }

            // 2. EMAIL УВЕДОМЛЕНИЕ (через Job)
            \App\Jobs\SendSubtaskAssignedNotification::dispatch($user, $subtask, $task, $role);
        }
        // ========= КОНЕЦ УВЕДОМЛЕНИЙ =========

        return response()->json(
            $subtask->load(['executors:id,name', 'responsibles:id,name', 'creator:id,name']),
            201
        );
    }







    public function show($id)
    {
        // 1. Начинаем запрос с отключения глобального скоупа
        $subtask = Subtask::withoutGlobalScope('not_completed')
            // 2. Используем with() вместо load(), чтобы загрузить связи сразу
            ->with([
                'task.project.company',
                'task.project.executors:id,name',
                'task.project.managers:id,name',
                'creator:id,name',
                'executors:id,name',
                'responsibles:id,name',
                'files.user:id,name',
                'checklist.responsible:id,name',
                'comments.user:id,name',
                'children.executors:id,name',
                'children.responsibles:id,name',
                'children.files.user:id,name',
            ])
            // 3. Ищем запись по ID. Если не найдет — будет 404
            ->findOrFail($id);

        $this->authorize('view', $subtask);

        return response()->json($subtask);
    }

    public function updateProgress(Request $request, Subtask $subtask)
    {
        $this->authorize('updateProgress', $subtask);

        $data = $request->validate([
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $subtask->update(['progress' => $data['progress']]);

        return response()->json([
            'message'  => 'Прогресс обновлён',
            'progress' => $subtask->progress,
        ]);
    }

    public function complete(Request $request, Subtask $subtask)
    {
        $this->authorize('complete', $subtask);

        if ((int) $subtask->progress < 100) {
            return response()->json([
                'message' => 'Подзадачу можно завершить только при прогрессе 100%.'
            ], 422);
        }

        if ($subtask->completed) {
            return response()->json([
                'message'      => 'Подзадача уже завершена.',
                'completed'    => true,
                'completed_at' => $subtask->completed_at,
            ]);
        }

        $subtask->forceFill([
            'completed'    => true,
            'completed_at' => now(),
        ])->save();

        return response()->json([
            'message'      => 'Подзадача завершена.',
            'completed'    => true,
            'completed_at' => $subtask->completed_at,
        ]);
    }


public function destroy(Subtask $subtask)
{
    $this->authorize('delete', $subtask);

    // удаляем подзадачу
    $subtask->delete();

    return response()->json(['message' => 'Подзадача успешно удалена']);
}


// 🔹 Изменить ответственного
public function changeResponsible(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
        'replace_user_id' => 'nullable|exists:users,id',
    ]);

    // ⚙️ Проверка: новый пользователь не должен уже быть ответственным
    if ($subtask->responsibles()->where('user_id', $data['user_id'])->exists()) {
        return response()->json([
            'message' => 'Этот пользователь уже является ответственным.',
        ], 422);
    }

    // ⚙️ Если есть кого заменить — удаляем только его
    if (!empty($data['replace_user_id'])) {
        $subtask->responsibles()->detach($data['replace_user_id']);
    }

    // ⚙️ Добавляем нового без удаления остальных
    $subtask->responsibles()->syncWithoutDetaching([$data['user_id']]);

    return response()->json([
        'message' => 'Ответственный успешно изменён.',
        'responsibles' => $subtask->responsibles()->get(['users.id', 'users.name']),
    ]);
}

// 🔹 Изменить исполнителя
// 🔹 Изменить исполнителя (точечная замена)
public function changeExecutor(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
        'replace_user_id' => 'nullable|exists:users,id',
    ]);

    // ⚙️ Проверка: новый пользователь не должен уже быть исполнителем
    if ($subtask->executors()->where('user_id', $data['user_id'])->exists()) {
        return response()->json([
            'message' => 'Этот пользователь уже является исполнителем.',
        ], 422);
    }

    // ⚙️ Если передан заменяемый — удаляем только его
    if (!empty($data['replace_user_id'])) {
        $subtask->executors()->detach($data['replace_user_id']);
    }

    // ⚙️ Добавляем нового
    $subtask->executors()->syncWithoutDetaching([$data['user_id']]);

    return response()->json([
        'message' => 'Исполнитель успешно изменён.',
        'executors' => $subtask->executors()->get(['users.id', 'users.name']),
    ]);
}


// 🔹 Добавить исполнителя
public function addExecutors(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_ids' => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
    ]);

    $subtask->executors()->syncWithoutDetaching($data['user_ids']);

    return response()->json([
        'message' => 'Исполнители добавлены',
    'executors' => $subtask->executors()->get(['users.id', 'users.name']),
    ]);
}

// 🔹 Добавить ответственного
public function addResponsibles(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_ids' => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
    ]);

    $subtask->responsibles()->syncWithoutDetaching($data['user_ids']);

    return response()->json([
        'message' => 'Ответственные добавлены',
    'responsibles' => $subtask->responsibles()->get(['users.id', 'users.name']),
    ]);
}

// Удалить исполнителя
public function removeExecutor(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    // Проверим, что хотя бы один исполнитель останется
    if ($subtask->executors()->count() <= 1) {
        return response()->json([
            'message' => 'Нельзя удалить всех исполнителей. В подзадаче должен остаться хотя бы один.',
        ], 422);
    }

    $subtask->executors()->detach($data['user_id']);

    return response()->json([
        'message' => 'Исполнитель удалён',
        'executors' => $subtask->executors()->get(['users.id', 'users.name']),
    ]);
}

// Удалить ответственного
public function removeResponsible(Request $request, Subtask $subtask)
{
    $this->authorize('manageMembers', $subtask);

    $data = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    // Проверим, что хотя бы один ответственный останется
    if ($subtask->responsibles()->count() <= 1) {
        return response()->json([
            'message' => 'Нельзя удалить всех ответственных. В подзадаче должен остаться хотя бы один.',
        ], 422);
    }

    $subtask->responsibles()->detach($data['user_id']);

    return response()->json([
        'message' => 'Ответственный удалён',
        'responsibles' => $subtask->responsibles()->get(['users.id', 'users.name']),
    ]);
}

public function update(Request $request, Subtask $subtask)
{
    $this->authorize('update', $subtask);

    $validated = $request->validate([
    'title' => 'required|string|max:255',
    'due_date' => 'required|date|after_or_equal:' . $subtask->start_date,
], [
    'title.required' => 'Введите название',
    'due_date.required' => 'Введите дату окончания',
    'due_date.after_or_equal' => 'Дата окончания не может быть раньше даты начала',
]);

    $subtask->update($validated);

    return response()->json([
        'message' => 'Подзадача обновлена успешно',
        'subtask' => $subtask->fresh(['executors:id,name', 'responsibles:id,name']),
    ]);
}



    public function uploadFile(Request $request, Subtask $subtask)
    {
        // Проверка прав
        $this->authorize('addFiles', $subtask);

        // Валидация
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar|max:51200', // до 50 МБ
            'requires_approval' => 'nullable|boolean',
        ], [
            'file.max' => 'Файл не должен превышать 50 МБ',
            'file.mimes' => 'Разрешены форматы: pdf, doc, docx, xls, xlsx, ppt, pptx, zip, rar',
        ]);

        $file = $request->file('file');
        $requiresApproval = $request->boolean('requires_approval');
        $status = $requiresApproval ? 'pending' : 'none';

        // Уникальное имя файла, чтобы не перезаписывать существующие
        $filename = time() . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('subtask_files', $filename, 'public');

        // Сохраняем в БД
        $subtaskFile = $subtask->files()->create([
            'user_id' => auth()->id(),
            'filename' => $file->getClientOriginalName(),
            'path' => $path,
            'status' => $status,
        ]);

        return response()->json($subtaskFile, 201);
    }



    public function sendForRevision(Request $request, SubtaskFile $file)
    {
        // 1. Валидация комментария
        $data = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        // 2. Проверка прав: Ответственный ИЛИ Исполнитель могут отправлять на доработку
        $subtask = $file->subtask;
        $userId = auth()->id();

        // Проверяем, является ли пользователь ответственным
        $isResponsible = $subtask->responsibles()
            ->where('user_id', $userId)
            ->exists();

        // Проверяем, является ли пользователь исполнителем
        $isExecutor = $subtask->executors()
            ->where('user_id', $userId)
            ->exists();

        // Проверяем, является ли пользователь создателем (опционально)
        $isCreator = $subtask->creator_id === $userId;

        // Если не ответственный и не исполнитель (и не создатель, если нужно)
        if (!$isResponsible && !$isExecutor) {
            return response()->json([
                'message' => 'Только ответственный или исполнитель может отправлять на доработку'
            ], 403);
        }

        // 3. Дополнительная проверка: нельзя отправить на доработку уже согласованный файл
        if ($file->status === 'approved') {
            return response()->json([
                'message' => 'Нельзя отправить на доработку согласованный файл'
            ], 400);
        }

        // 4. Обновление файла
        $file->update([
            'status' => 'revision',
            'revision_comment' => $data['comment'],
            'approved_at' => null,
            'approved_by' => null,
        ]);



        return response()->json([
            'message' => 'Файл отправлен на доработку',
            'file' => $file
        ]);
    }

    public function replaceFile(Request $request, SubtaskFile $file)
    {
        // Проверка прав (такая же, как при добавлении файлов в подзадачу)
        // $this->authorize('addFiles', $file->subtask);
        // Или, если у вас своя логика, убедитесь, что юзер имеет право менять файлы

        $request->validate([
            'file' => 'required|file|max:10240', // 10 MB
        ]);

        // 1. Удаляем старый файл с диска
        if (Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        // 2. Сохраняем новый файл
        $newFile = $request->file('file');
        $newPath = $newFile->store('subtask_files', 'public');

        // 3. Обновляем запись в БД
        $file->update([
            'filename' => $newFile->getClientOriginalName(),
            'path'     => $newPath,
            'status'   => 'ok', // Сбрасываем статус
            'revision_comment' => null, // Удаляем комментарий
        ]);

        return response()->json(['message' => 'Файл обновлен', 'file' => $file]);
    }

public function downloadFile(SubtaskFile $file)
{
    return Storage::disk('public')->download($file->path, $file->filename);
}

    public function deleteFile(SubtaskFile $file)
    {
        $user = auth()->user();
        $subtask = $file->subtask;

        // Могут удалить:
        // 1. Создатель файла
        // 2. Создатель подзадачи (опционально)


        $isFileCreator = $file->user_id === $user->id;
        $isSubtaskCreator = $subtask->creator_id === $user->id;


        if (!$isFileCreator && !$isSubtaskCreator) {
            return response()->json([
                'message' => 'У вас нет прав на удаление этого файла'
            ], 403);
        }

        $this->authorize('addFiles', $subtask);

        Storage::disk('public')->delete($file->path);
        $file->delete();

        return response()->json(['message' => 'Файл удалён']);
    }

// app/Http/Controllers/SubtaskController.php
public function storeChild(Request $request, Subtask $subtask)
{
   $this->authorize('createSubtask', $subtask);

    $data = $request->validate([
        'title' => 'required|string|max:255',
        'start_date' => 'nullable|date',
        'due_date' => 'nullable|date',
        'executor_ids' => 'required|array|min:1',
        'executor_ids.*' => 'exists:users,id',
        'responsible_ids' => 'required|array|min:1',
        'responsible_ids.*' => 'exists:users,id',
    ]);

    $child = $subtask->children()->create([
        'title'      => $data['title'],
        'start_date' => $data['start_date'] ?? now(),
        'due_date'   => $data['due_date'],
        'creator_id' => auth()->id(),
        'task_id'    => $subtask->task_id,
        'parent_id'  => $subtask->id,
    ]);

    $child->executors()->sync($data['executor_ids']);
    $child->responsibles()->sync($data['responsible_ids']);

    return response()->json($child->load(['executors:id,name', 'responsibles:id,name']), 201);
}


private function canComment($user, $subtask)
{
    $project = $subtask->task->project;

    return
        $user->id === $subtask->creator_id ||               // автор подзадачи
        $user->id === $project->company->user_id ||         // владелец компании
        $project->executors->contains('id', $user->id) ||   // исполнители проекта
        $project->managers->contains('id', $user->id) ||    // руководитель проекта
        $subtask->executors->contains('id', $user->id) ||   // исполнитель подзадачи
        $subtask->responsibles->contains('id', $user->id);  // ответственный подзадачи
}



    public function addComment(Request $request, $subtaskId)
    {
        $user = $request->user();

        $subtask = Subtask::with([
            'executors',
            'responsibles',
            'task.project.executors',
            'task.project.managers',
            'task.project.company'
        ])->findOrFail($subtaskId);

        abort_unless($this->canComment($user, $subtask), 403);

        $data = $request->validate([
            'comment'   => 'required|string|max:2000',
            'mentions'  => 'array',
            'parent_id' => 'nullable|exists:subtask_comments,id' // Валидация родителя
        ]);

        $comment = SubtaskComment::create([
            'subtask_id' => $subtask->id,
            'user_id'    => $user->id,
            'comment'    => $data['comment'],
            'mentions'   => json_encode($data['mentions'] ?? []),
            'parent_id'  => $data['parent_id'] ?? null,
        ]);

        // Загружаем связи для ответа фронтенду и для уведомлений
        $comment->load(['user:id,name', 'parent.user']);

        // Списки ID для исключения повторных уведомлений
        $notifiedUserIds = [];

        // ===============================================================
        // 🔔 1. УПОМИНАНИЯ (@username)
        // ===============================================================
        if (!empty($data['mentions'])) {
            foreach ($data['mentions'] as $uid) {
                if ($uid == $user->id) continue; // Себя не уведомляем

                $u = User::find($uid);
                if ($u && $u->telegram_chat_id) {
                    \App\Services\TelegramService::sendMessage(
                        $u->telegram_chat_id,
                        "🔔 Вас упомянули в подзадаче:\n".
                        "<b>{$subtask->title}</b>\n\n".
                        "<b>{$user->name}</b> написал:\n{$data['comment']}"
                    );
                    $notifiedUserIds[] = $u->id;
                }
            }
        }

        // ===============================================================
        // 🔔 2. ОТВЕТ НА СООБЩЕНИЕ (REPLY)
        // ===============================================================
        // Если это ответ, и автор родительского коммента не я, и его еще не уведомили через @mention
        if ($comment->parent && $comment->parent->user_id !== $user->id) {
            $parentAuthor = $comment->parent->user;

            if ($parentAuthor && !in_array($parentAuthor->id, $notifiedUserIds)) {
                if ($parentAuthor->telegram_chat_id) {
                    \App\Services\TelegramService::sendMessage(
                        $parentAuthor->telegram_chat_id,
                        "↩️ <b>Ответ на ваш комментарий</b> в подзадаче:\n".
                        "<b>{$subtask->title}</b>\n\n".
                        "<b>{$user->name}</b>: {$comment->comment}"
                    );
                }
                $notifiedUserIds[] = $parentAuthor->id;
            }
        }

        // ===============================================================
        // 🔔 3. ОБЩЕЕ УВЕДОМЛЕНИЕ (если не было личных тегов и ответов)
        // ===============================================================
        // Логика: если мы ответили кому-то лично или тегнули кого-то,
        // часто не нужно спамить всем остальным. Но если хотите уведомлять всегда,
        // уберите условие if (empty($notifiedUserIds)).

        if (empty($notifiedUserIds)) {
            $participants = collect([]);

            // Ответственные
            $participants = $participants->merge(
                DB::table('subtask_responsibles')->where('subtask_id', $subtask->id)->pluck('user_id')
            );

            // Исполнители
            $participants = $participants->merge(
                DB::table('subtask_executors')->where('subtask_id', $subtask->id)->pluck('user_id')
            );

            // Уникальные ID
            $participants = $participants->unique();

            // Исключаем автора и тех, кого уже уведомили (если бы мы убрали if выше)
            $participants = $participants->reject(fn($id) => $id == $user->id || in_array($id, $notifiedUserIds));

            $users = User::whereIn('id', $participants)->get();

            foreach ($users as $u) {
                if ($u->telegram_chat_id) {
                    \App\Services\TelegramService::sendMessage(
                        $u->telegram_chat_id,
                        "💬 Новое сообщение в подзадаче:\n".
                        "<b>{$subtask->title}</b>\n\n".
                        "Автор: <b>{$user->name}</b>\n".
                        "Комментарий:\n{$data['comment']}"
                    );
                }
            }
        }

        return response()->json($comment);
    }




public function updateComment(Request $request, $id)
{
    $user = $request->user();

    $comment = SubtaskComment::with([
        'subtask.executors',
        'subtask.responsibles',
        'subtask.task.project.executors',
        'subtask.task.project.managers',
        'subtask.task.project.company'
    ])->findOrFail($id);

    abort_unless(
        $comment->user_id === $user->id ||
        $this->canComment($user, $comment->subtask),
        403
    );

    $data = $request->validate([
        'comment' => 'required|string|max:2000',
    ]);

    $comment->update([
        'comment' => $data['comment'],
        'mentions' => json_encode($request->mentions ?? [])
    ]);

    return response()->json($comment->fresh()->load('user:id,name'));
}


public function deleteComment(Request $request, $id)
{
    $user = $request->user();

    $comment = SubtaskComment::with([
        'subtask.executors',
        'subtask.responsibles',
        'subtask.task.project.executors',
        'subtask.task.project.managers',
        'subtask.task.project.company'
    ])->findOrFail($id);

    abort_unless(
        $comment->user_id === $user->id ||
        $this->canComment($user, $comment->subtask),
        403
    );

    $comment->delete();

    return response()->json(['status' => 'ok']);
}


public function updateDescription(Request $request, Subtask $subtask)
{
    $user = $request->user();

    // Только автор подзадачи может редактировать
    abort_unless($user->id === $subtask->creator_id, 403, 'Нет прав изменять описание');

    $data = $request->validate([
        'description' => 'nullable|string|max:5000',
    ]);

    $subtask->description = $data['description'];
    $subtask->save();

    return response()->json([
        'status' => 'ok',
        'description' => $subtask->description
    ]);
}

    public function startWork(Request $request, Subtask $subtask)
    {
        $user = $request->user();

        // Проверяем, является ли пользователь исполнителем (или имеет права на управление)
        $isExecutor = $subtask->executors->contains('id', $user->id);

        // Если хотите разрешить брать в работу и менеджерам, добавьте $this->authorize('update', $subtask)
        abort_unless($isExecutor || $subtask->creator_id == $user->id, 403, 'Только исполнитель может взять подзадачу в работу.');

        if ($subtask->status === 'in_work') {
            return response()->json(['message' => 'Подзадача уже в работе.'], 422);
        }

        $subtask->update([
            'status' => 'in_work',
        ]);

        // УВЕДОМЛЕНИЕ ОТВЕТСТВЕННЫМ И АВТОРУ
        // Собираем получателей: Ответственные + Автор (исключая себя)
        $recipients = collect([]);
        $recipients = $recipients->merge($subtask->responsibles);
        if ($subtask->creator && $subtask->creator_id !== $user->id) {
            $recipients->push($subtask->creator);
        }

        $recipients = $recipients->unique('id')->reject(fn($u) => $u->id === $user->id);

        foreach ($recipients as $recipient) {
            if ($recipient->telegram_chat_id) {
                \App\Services\TelegramService::sendMessage(
                    $recipient->telegram_chat_id,
                    "🚀 <b>Подзадача взята в работу!</b>\n".
                    "Подзадача: <b>{$subtask->title}</b>\n".
                    "Исполнитель: {$user->name}\n"
                );
            }
        }

        return response()->json([
            'message' => 'Статус подзадачи изменен на "В работе"',
            'subtask' => $subtask->fresh(['executors:id,name', 'responsibles:id,name']),
        ]);
    }

    public function restore($id)
    {
        // Ищем подзадачу, игнорируя глобальные скоупы
        $subtask = Subtask::withoutGlobalScope('not_completed')->findOrFail($id);

        $subtask->update([
            'completed' => 0,
            'progress' => 0,
            'completed_at' => null,
        ]);

        return back()->with('success', 'Подзадача восстановлена');
    }


    public function approve(Request $request, SubtaskFile $file)
    {
        $subtask = $file->subtask;
        $user = $request->user();

        // Проверка, является ли пользователь участником подзадачи
        $isParticipant = $subtask->creator_id === $user->id ||
            $subtask->executors()->where('user_id', $user->id)->exists() ||
            $subtask->responsibles()->where('user_id', $user->id)->exists();

        if (!$isParticipant) {
            return response()->json(['message' => 'У вас нет прав на согласование'], 403);
        }

        if ($file->status === 'revision') {
            return response()->json(['message' => 'Сначала нужно исправить замечания'], 400);
        }

        if ($file->status === 'approved') {
            return response()->json(['message' => 'Файл уже согласован'], 400);
        }

        $file->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $user->id,
            'revision_comment' => null,
            'revision_by' => null,
        ]);

        return response()->json(['message' => 'Файл согласован', 'file' => $file]);
    }

    /**
     * Отклонение файла (альтернатива revision)
     */
    public function revision(Request $request, SubtaskFile $file)
    {
        $request->validate([
            'comment' => 'required|string|max:1000'
        ]);

        // Проверка прав
        $subtask = $file->subtask;
        $user = $request->user();

        $isResponsible = $subtask->responsibles()->where('user_id', $user->id)->exists();

        if (!$isResponsible) {
            return response()->json(['message' => 'У вас нет прав'], 403);
        }

        $file->update([
            'approval_status' => 'revision',
            'revision_comment' => $request->comment,
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return response()->json(['message' => 'Файл отправлен на доработку', 'file' => $file]);
    }



}
