<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subtask;
use App\Models\SubtaskChecklist;

class SubtaskChecklistController extends Controller
{
    // Базовая проверка: имеет ли юзер доступ к самой подзадаче (просмотр/участие)
    private function canAccessSubtask($user, $subtask)
    {
        $project = $subtask->task->project;

        return
            $user->id === $subtask->creator_id ||
            $project->company->user_id === $user->id ||
            $project->managers->contains('id', $user->id) ||
            $project->executors->contains('id', $user->id) ||
            $subtask->executors->contains('id', $user->id) ||
            $subtask->responsibles->contains('id', $user->id);
    }

    // Проверка прав на изменение конкретного пункта
    private function checkItemPermissions($user, $item)
    {
        // 1. Сначала проверяем доступ к подзадаче в целом
        if (!$this->canAccessSubtask($user, $item->subtask)) {
            abort(403, 'Нет доступа к подзадаче.');
        }

        // 2. Если у пункта есть создатель, и это не текущий юзер — запрет
        // (Если creator_id == null, то пропускаем — разрешено всем участникам)
        if ($item->creator_id !== null && $item->creator_id !== $user->id) {
            abort(403, 'Вы не можете редактировать этот пункт, так как он создан другим пользователем.');
        }
    }

    public function store(Request $request, $subtaskId)
    {
        $user = $request->user();
        $subtask = Subtask::with(['task.project'])->findOrFail($subtaskId);

        abort_unless($this->canAccessSubtask($user, $subtask), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'responsible_id' => 'nullable|exists:users,id',
        ]);

        $item = SubtaskChecklist::create([
            'subtask_id' => $subtask->id,
            'title' => $data['title'],
            'responsible_id' => $data['responsible_id'] ?? null,
            'creator_id' => $user->id, // Записываем создателя
        ]);

        return response()->json($item->load('responsible:id,name', 'creator:id,name'));
    }

    public function update(Request $request, $id)
    {
        $item = SubtaskChecklist::with('subtask.task.project')->findOrFail($id);

        // Проверяем права (Создатель или null)
        $this->checkItemPermissions($request->user(), $item);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'responsible_id' => 'nullable|exists:users,id',
        ]);

        $item->update($data);

        return response()->json($item->fresh()->load('responsible:id,name', 'creator:id,name'));
    }

    public function toggle(Request $request, $id)
    {
        $item = SubtaskChecklist::with('subtask.task.project')->findOrFail($id);
        $user = $request->user();

        // Тогглить (ставить галочку) обычно разрешают всем участникам,
        // даже если создал другой. Если хотите строго — используйте checkItemPermissions
        abort_unless($this->canAccessSubtask($user, $item->subtask), 403);

        $item->completed = !$item->completed;
        $item->save();

        return response()->json(['completed' => $item->completed]);
    }

    public function destroy(Request $request, $id)
    {
        $item = SubtaskChecklist::with('subtask.task.project')->findOrFail($id);

        // Проверяем права (Создатель или null)
        $this->checkItemPermissions($request->user(), $item);

        $item->delete();
        return response()->json(['status' => 'ok', 'id' => $id]);
    }
}
