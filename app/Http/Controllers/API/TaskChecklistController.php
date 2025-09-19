<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskChecklist;
use Illuminate\Http\Request;

class TaskChecklistController extends Controller
{
    public function index(Task $task)
    {
        $this->authorize('view', $task);

        return $task->checklists()->with(['assignee:id,name', 'files'])->get();
    }

    public function store(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'important' => 'boolean',
            'files.*' => 'nullable|file|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg|max:5120',
        ]);

        $checklist = $task->checklists()->create($validated);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('checklist_files', 'public');
                $checklist->files()->create(['file_path' => $path]);
            }
        }

        if (!empty($validated['assigned_to'])) {
        $user = \App\Models\User::find($validated['assigned_to']);
        if ($user && $user->telegram_chat_id) {
            \App\Services\TelegramService::sendMessage(
                $user->telegram_chat_id,
                "📝 Вам назначен новый пункт чеклиста: <b>{$checklist->title}</b>\n".
                "Задача: {$task->title}\n".
                ($validated['important'] ? "⚠️ Важно!" : "")
            );
        }
    }

        return response()->json($checklist->load('assignee', 'files'), 201);
    }

    public function toggle(TaskChecklist $checklist)
    {
        $this->authorize('update', $checklist->task);

        $checklist->update(['completed' => !$checklist->completed]);

        return response()->json($checklist);
    }
}