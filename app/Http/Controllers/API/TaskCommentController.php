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
