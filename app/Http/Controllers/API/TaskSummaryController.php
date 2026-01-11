<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Subtask; // <--- Не забудьте импорт
use App\Models\Company;
use Illuminate\Support\Facades\Auth;

class TaskSummaryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $mode = $request->get('mode', 'my_tasks');
        $filterUserId = $request->get('user_id');

        // Новый параметр: 'all', 'task', 'subtask'
        $filterType = $request->get('type', 'all');

        $summary = [];

        $initUser = function ($u) use (&$summary) {
            if (!isset($summary[$u->id])) {
                $summary[$u->id] = [
                    'user' => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'avatar' => $u->avatar ?? null
                    ],
                    'stats' => [
                        'in_work_count' => 0,
                        'overdue_count' => 0,
                        'completed_count' => 0,
                        'total' => 0
                    ],
                    'tasks' => [
                        'in_work' => [],
                        'overdue' => [],
                        'completed' => []
                    ]
                ];
            }
        };

        // ==========================================
        // 1. ЗАГРУЗКА ГЛАВНЫХ ЗАДАЧ (TASKS)
        // ==========================================
        // Выполняем, только если фильтр НЕ "только подзадачи"
        if ($filterType !== 'subtask') {
            $taskQuery = Task::with([
                'executors:id,name',
                'responsibles:id,name',
                'watcherstask:id,name',
                'project:id,name'
            ]);

            if ($mode === 'owner') {
                $companyIds = Company::where('user_id', $user->id)->pluck('id');
                $taskQuery->whereIn('company_id', $companyIds);
            } elseif ($mode === 'author') {
                $taskQuery->where('creator_id', $user->id);
            } else {
                $taskQuery->where(function ($q) use ($user) {
                    $q->whereHas('executors', fn($sub) => $sub->where('users.id', $user->id))
                        ->orWhereHas('responsibles', fn($sub) => $sub->where('users.id', $user->id))
                        ->orWhereHas('watcherstask', fn($sub) => $sub->where('users.id', $user->id));
                });
            }

            $tasks = $taskQuery->get();

            foreach ($tasks as $task) {
                $targetUsers = collect([]);
                if ($mode === 'my_tasks') {
                    $targetUsers->push($user);
                } else {
                    $targetUsers = $targetUsers->merge($task->executors);
                    if ($targetUsers->isEmpty()) $targetUsers = $targetUsers->merge($task->responsibles);
                }

                if ($filterUserId) $targetUsers = $targetUsers->filter(fn($u) => $u->id == $filterUserId);

                foreach ($targetUsers as $targetUser) {
                    $initUser($targetUser);
                    $uid = $targetUser->id;

                    $category = 'in_work';
                    $isOverdue = false;
                    if ($task->completed) {
                        $category = 'completed';
                    } elseif ($task->due_date && \Carbon\Carbon::parse($task->due_date)->endOfDay()->isPast()) {
                        $category = 'overdue';
                        $isOverdue = true;
                    }

                    $roles = [];
                    if ($task->executors->contains('id', $uid)) $roles[] = 'Исполнитель';
                    if ($task->responsibles->contains('id', $uid)) $roles[] = 'Ответственный';
                    if ($task->watcherstask->contains('id', $uid)) $roles[] = 'Наблюдатель';

                    $summary[$uid]['tasks'][$category][] = [
                        'id' => $task->id,
                        'is_subtask' => false,
                        'title' => $task->title,
                        'due_date' => $task->due_date,
                        'project_name' => $task->project->name ?? 'Без проекта',
                        'priority' => $task->priority,
                        'roles' => implode(', ', $roles),
                        'is_overdue' => $isOverdue,
                        'link' => "/tasks/{$task->id}"
                    ];
                    $summary[$uid]['stats'][$category . '_count']++;
                    $summary[$uid]['stats']['total']++;
                }
            }
        }

        // ==========================================
        // 2. ЗАГРУЗКА ПОДЗАДАЧ (SUBTASKS)
        // ==========================================
        // Выполняем, только если фильтр НЕ "только задачи"
        if ($filterType !== 'task') {
            $subtaskQuery = Subtask::with([
                'executors:id,name',
                'responsibles:id,name',
                'task.project:id,name'
            ]);

            if ($mode === 'owner') {
                $companyIds = Company::where('user_id', $user->id)->pluck('id');
                $subtaskQuery->whereHas('task', function($q) use ($companyIds) {
                    $q->whereIn('company_id', $companyIds);
                });
            } elseif ($mode === 'author') {
                $subtaskQuery->where('creator_id', $user->id);
            } else {
                $subtaskQuery->where(function ($q) use ($user) {
                    $q->whereHas('executors', fn($sub) => $sub->where('users.id', $user->id))
                        ->orWhereHas('responsibles', fn($sub) => $sub->where('users.id', $user->id));
                });
            }

            $subtasks = $subtaskQuery->get();

            foreach ($subtasks as $sub) {
                $targetUsers = collect([]);
                if ($mode === 'my_tasks') {
                    $targetUsers->push($user);
                } else {
                    $targetUsers = $targetUsers->merge($sub->executors);
                    if ($targetUsers->isEmpty()) $targetUsers = $targetUsers->merge($sub->responsibles);
                }

                if ($filterUserId) $targetUsers = $targetUsers->filter(fn($u) => $u->id == $filterUserId);

                foreach ($targetUsers as $targetUser) {
                    $initUser($targetUser);
                    $uid = $targetUser->id;

                    $category = 'in_work';
                    $isOverdue = false;
                    if ($sub->completed) {
                        $category = 'completed';
                    } elseif ($sub->due_date && \Carbon\Carbon::parse($sub->due_date)->endOfDay()->isPast()) {
                        $category = 'overdue';
                        $isOverdue = true;
                    }

                    $roles = [];
                    if ($sub->executors->contains('id', $uid)) $roles[] = 'Исполнитель';
                    if ($sub->responsibles->contains('id', $uid)) $roles[] = 'Ответственный';

                    $summary[$uid]['tasks'][$category][] = [
                        'id' => $sub->id,
                        'is_subtask' => true,
                        'title' => $sub->title,
                        'due_date' => $sub->due_date,
                        'project_name' => $sub->task->project->name ?? 'Без проекта',
                        'priority' => null,
                        'roles' => implode(', ', $roles),
                        'is_overdue' => $isOverdue,
                        'link' => "/tasks/{$sub->task_id}"
                    ];
                    $summary[$uid]['stats'][$category . '_count']++;
                    $summary[$uid]['stats']['total']++;
                }
            }
        }

        $result = collect($summary)->sortBy('user.name')->values();
        $availableUsers = $result->map(fn($item) => $item['user']);

        return response()->json([
            'summary' => $result,
            'users' => $availableUsers,
            'is_owner' => Company::where('user_id', $user->id)->exists()
        ]);
    }
}
