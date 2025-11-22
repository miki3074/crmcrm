<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Subtask; 

use App\Models\SubtaskChecklist; 


class SubtaskChecklistController extends Controller
{
    private function canManage($user, $subtask)
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

    public function store(Request $request, $subtaskId)
    {
        $user = $request->user();

        $subtask = Subtask::with([
            'executors', 'responsibles',
            'task.project.managers',
            'task.project.executors',
            'task.project.company'
        ])->findOrFail($subtaskId);

        abort_unless($this->canManage($user, $subtask), 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'responsible_id' => 'nullable|exists:users,id',
        ]);

        $item = SubtaskChecklist::create([
            'subtask_id' => $subtask->id,
            'title' => $data['title'],
            'responsible_id' => $data['responsible_id'] ?? null,
            'creator_id' => $user->id,
        ]);

        return response()->json($item->load('responsible:id,name'));
    }

    public function update(Request $request, $id)
    {
        $item = SubtaskChecklist::with([
            'subtask.executors',
            'subtask.responsibles',
            'subtask.task.project.managers',
            'subtask.task.project.executors',
            'subtask.task.project.company'
        ])->findOrFail($id);

        $user = $request->user();
        abort_unless($this->canManage($user, $item->subtask), 403);

        $item->update($request->only('title', 'responsible_id'));

        return response()->json($item->fresh()->load('responsible:id,name'));
    }

    public function toggle(Request $request, $id)
    {
        $item = SubtaskChecklist::with('subtask')->findOrFail($id);
        $user = $request->user();

        abort_unless($this->canManage($user, $item->subtask), 403);

        $item->completed = !$item->completed;
        $item->save();

        return response()->json(['completed' => $item->completed]);
    }

    public function destroy($id)
    {
        $item = SubtaskChecklist::with([
            'subtask.executors',
            'subtask.responsibles',
            'subtask.task.project.managers',
            'subtask.task.project.executors',
            'subtask.task.project.company'
        ])->findOrFail($id);

        $user = request()->user();
        abort_unless($this->canManage($user, $item->subtask), 403);

        $item->delete();
        return response()->json(['status' => 'ok']);
    }
}

