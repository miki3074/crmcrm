<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\SendChecklistNotification;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TaskChecklistController extends Controller
{
    public function index(Task $task)
    {
        $this->authorize('view', $task);

        return $task->checklists()->with(['assignee:id,name', 'files'])->get();
    }

    public function store(Request $request, Task $task)
    {
        $this->authorize('list', $task);

        $messages = [
            'title.required' => 'Введите название пункта чек-листа.',
            'title.max' => 'Название не должно превышать :max символов.',
            'assigned_to.exists' => 'Выбранный ответственный не найден.',
            'files.*.mimes' => 'Можно прикреплять только файлы PDF, Word, Excel или изображения.',
            'files.*.max' => 'Размер каждого файла не должен превышать 5 МБ.',
        ];

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'important' => 'boolean',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:5120',
        ], $messages);

        $validated['created_by'] = $request->user()->id;

        $checklist = $task->checklists()->create($validated);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('checklist_files', 'public');
                $checklist->files()->create(['file_path' => $path]);
            }
        }

        // ========= ОТПРАВКА УВЕДОМЛЕНИЙ =========
        if (!empty($validated['assigned_to'])) {
            // Отправляем уведомление конкретному пользователю
            $assignedUserId = $validated['assigned_to'];

            // Email уведомление через Job
            SendChecklistNotification::dispatch($checklist, $assignedUserId);

            // Telegram уведомление (опционально)
            $user = \App\Models\User::find($assignedUserId);
            if ($user && $user->telegram_chat_id) {
                TelegramService::sendMessage(
                    $user->telegram_chat_id,
                    "📝 Вам назначен новый пункт чек-листа: <b>{$checklist->title}</b>\n" .
                    "📌 Задача: {$task->title}\n" .
                    ($validated['important'] ?? false ? "⚠️ Важно!\n" : "") .
                    "🔗 <a href=\"" . url("/tasks/{$task->id}") . "\">Открыть задачу</a>"
                );
            }
        } else {
            // Отправляем уведомление всем участникам задачи
            SendChecklistNotification::dispatch($checklist);

            // Telegram уведомления всем участникам (опционально)
            $recipients = collect()
                ->merge($task->executors)
                ->merge($task->responsibles)
                ->merge($task->watcherstask)
                ->unique('id');

            foreach ($recipients as $user) {
                if ($user && $user->telegram_chat_id) {
                    TelegramService::sendMessage(
                        $user->telegram_chat_id,
                        "📝 Новый пункт чек-листа в задаче: <b>{$checklist->title}</b>\n" .
                        "📌 Задача: {$task->title}\n" .
                        ($validated['important'] ?? false ? "⚠️ Важно!\n" : "") .
                        "🔗 <a href=\"" . url("/tasks/{$task->id}") . "\">Открыть задачу</a>"
                    );
                }
            }
        }
        // ========= КОНЕЦ ОТПРАВКИ УВЕДОМЛЕНИЙ =========

        return response()->json($checklist->load('assignee', 'files', 'creator'), 201);
    }

    public function toggle(TaskChecklist $checklist)
    {
        $this->authorize('update', $checklist->task);

        $checklist->update(['completed' => !$checklist->completed]);

        // Опционально: уведомление о выполнении чек-листа
        if ($checklist->completed) {
            $this->sendChecklistCompletedNotification($checklist);
        }

        return response()->json($checklist);
    }

    public function update(Request $request, TaskChecklist $checklist)
    {
        $this->checkPermission($checklist);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'important' => 'boolean',
        ]);

        $oldAssignedTo = $checklist->assigned_to;
        $checklist->update($validated);

        // Если изменился ответственный, отправляем уведомление новому
        if ($oldAssignedTo != $validated['assigned_to'] && !empty($validated['assigned_to'])) {
            SendChecklistNotification::dispatch($checklist, $validated['assigned_to']);

            // Telegram уведомление
            $user = \App\Models\User::find($validated['assigned_to']);
            if ($user && $user->telegram_chat_id) {
                TelegramService::sendMessage(
                    $user->telegram_chat_id,
                    "📝 Вам переназначен пункт чек-листа: <b>{$checklist->title}</b>\n" .
                    "📌 Задача: {$checklist->task->title}\n" .
                    "🔗 <a href=\"" . url("/tasks/{$checklist->task->id}") . "\">Открыть задачу</a>"
                );
            }
        }

        return response()->json($checklist->load('assignee', 'creator'));
    }

    public function destroy(TaskChecklist $checklist)
    {
        $this->checkPermission($checklist);

        // Удаляем файлы с диска
        foreach ($checklist->files as $file) {
            Storage::disk('public')->delete($file->file_path);
            $file->delete();
        }

        $checklist->delete();

        return response()->json(['message' => 'Deleted']);
    }

    // Вспомогательный метод для отправки уведомления о выполнении
    private function sendChecklistCompletedNotification(TaskChecklist $checklist)
    {
        $task = $checklist->task;

        // Уведомляем создателя чек-листа и ответственных
        $recipients = collect();

        if ($checklist->created_by) {
            $recipients->push($checklist->created_by);
        }

        $recipients = $recipients->merge($task->responsibles->pluck('id'));
        $recipients = $recipients->unique();

        foreach ($recipients as $userId) {
            $user = \App\Models\User::find($userId);
            if ($user && $user->email) {
                // Здесь можно создать отдельное уведомление о выполнении
                // Или отправить через существующее
                $user->notify(new \App\Notifications\ChecklistCompletedNotification($checklist));
            }
        }
    }

    private function checkPermission($checklist)
    {
        // Сначала проверяем, имеет ли пользователь вообще доступ к задаче
        $this->authorize('update', $checklist->task);

        // Теперь специфика чек-листа
        if ($checklist->created_by !== null && $checklist->created_by !== auth()->id()) {
            abort(403, 'Вы не можете редактировать или удалять этот пункт, так как он создан другим пользователем.');
        }
    }
}
