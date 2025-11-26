<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Task;
use App\Models\Subtask;
use DB;

class TaskAccessController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        // Все задачи, к которым есть доступ
        $tasks = Task::query()
            ->select('tasks.id', 'tasks.title')
            ->where('tasks.creator_id', $userId)
            ->orWhereIn('tasks.id', function($q) use ($userId) {
                $q->select('task_id')->from('task_responsibles')->where('user_id', $userId);
            })
            ->orWhereIn('tasks.id', function($q) use ($userId) {
                $q->select('task_id')->from('task_executors')->where('user_id', $userId);
            })
            ->orWhereIn('tasks.id', function($q) use ($userId) {
                $q->select('task_id')->from('task_user_watchers')->where('user_id', $userId);
            })
            ->get();

        // Подзадачи сгруппируем по task_id
        $subtasks = [];

        foreach ($tasks as $task) {
            $subtasks[$task->id] = Subtask::query()
                ->select('id', 'title', 'task_id')
                ->where('task_id', $task->id)
                ->where(function ($q) use ($userId) {
                    $q->where('creator_id', $userId)
                      ->orWhereIn('id', function($q2) use ($userId){
                          $q2->select('subtask_id')->from('subtask_responsibles')->where('user_id', $userId);
                      })
                      ->orWhereIn('id', function($q2) use ($userId){
                          $q2->select('subtask_id')->from('subtask_executors')->where('user_id', $userId);
                      });
                })
                ->get();
        }

        return response()->json([
            'tasks' => $tasks,
            'subtasks' => $subtasks
        ]);
    }
}
