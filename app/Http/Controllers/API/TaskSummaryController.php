<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\Subtask;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
class TaskSummaryController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $mode = $request->get('mode', 'my_tasks');
        $filterUserId = $request->get('user_id');

        // Новый параметр: 'all', 'task', 'subtask'
        $filterType = $request->get('type', 'all');

        $filterStatus = $request->get('status', 'all');

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
        if ($filterType !== 'subtask') {
            // 👇 ИСПРАВЛЕНИЕ ЗДЕСЬ: отключаем фильтр незавершенных задач
            $taskQuery = Task::withoutGlobalScope('not_completed')
                ->with([
                    'executors:id,name',
                    'responsibles:id,name',
                    'watcherstask:id,name',
                    'project:id,name',
                    'company:id,name'
                ]);

            if ($filterStatus === 'completed') {
                $taskQuery->where('completed', 1);
            } elseif ($filterStatus === 'active') { // На будущее, если нужно только активные
                $taskQuery->where('completed', 0);
            }

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

                    // Теперь это условие будет работать, так как завершенные задачи пришли из БД
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
                        'company_name' => $task->company->name ?? 'Без компании',
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
        if ($filterType !== 'task') {

            // 👇 ЕСЛИ В МОДЕЛИ Subtask ТОЖЕ ЕСТЬ GlobalScope, РАСКОММЕНТИРУЙТЕ withoutGlobalScope НИЖЕ
            $subtaskQuery = Subtask::
             withoutGlobalScope('not_completed')->
            with([
                'executors:id,name',
                'responsibles:id,name',
                'task.project:id,name',
                'task.company:id,name'
            ]);

            if ($filterStatus === 'completed') {
                $subtaskQuery->where('completed', 1);
            } elseif ($filterStatus === 'active') {
                $subtaskQuery->where('completed', 0);
            }

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
                        'company_name' => $sub->task->company->name ?? 'Без компании',
                        'priority' => null,
                        'roles' => implode(', ', $roles),
                        'is_overdue' => $isOverdue,
                        'link' => "/subtasks/{$sub->id}"
                    ];
                    $summary[$uid]['stats'][$category . '_count']++;
                    $summary[$uid]['stats']['total']++;
                }
            }
        }

        $result = collect($summary)->sortBy('user.name')->values();
        $availableUsers = $result->map(fn($item) => $item['user']);

        // ДОБАВЛЕНО: Списки для фильтров в отчете
        $isOwner = Company::where('user_id', $user->id)->exists();

        // Получаем доступные компании и проекты
        // Если владелец - все свои, иначе - те где участвует (можно упростить до "всех" для фильтра)
        if ($isOwner || $mode === 'owner') {
            $metaCompanies = Company::select('id', 'name')->get();
            $metaProjects = Project::select('id', 'name', 'company_id')->get();
        } else {
            // Для обычного юзера можно показать только те, где он есть,
            // но для простоты берем все, или пустой список, если хотите ограничить.
            $metaCompanies = Company::select('id', 'name')->get();
            $metaProjects = Project::select('id', 'name', 'company_id')->get();
        }

        return response()->json([
            'summary' => $result,
            'users' => $availableUsers,
            'is_owner' => $isOwner,
            'meta' => [
                'companies' => $metaCompanies,
                'projects' => $metaProjects
            ]
        ]);
    }

    // --- НОВЫЙ МЕТОД ЭКСПОРТА ---
    public function export(Request $request)
    {
        $currentUser = Auth::user();
        $mode = $request->get('mode', 'my_tasks');
        $targetUserId = $request->get('user_id');
        $companyId = $request->get('company_id');
        $projectId = $request->get('project_id');

        // Сбор данных (упрощенная выборка для списка)
        $tasksCollection = collect();

        // 1. ЗАДАЧИ
        $taskQuery = Task::with(['company', 'project', 'executors', 'responsibles'])
            ->withoutGlobalScope('not_completed');

        // Фильтры доступа (Mode)
        if ($mode === 'owner') {
            $myCompanyIds = Company::where('user_id', $currentUser->id)->pluck('id');
            $taskQuery->whereIn('company_id', $myCompanyIds);
        } elseif ($mode === 'author') {
            $taskQuery->where('creator_id', $currentUser->id);
        } elseif ($mode === 'my_tasks') {
            // Строгий фильтр: "Мои задачи" - это где я исполнитель или ответственный
            $taskQuery->where(function ($q) use ($currentUser) {
                $q->whereHas('executors', fn($s) => $s->where('users.id', $currentUser->id))
                    ->orWhereHas('responsibles', fn($s) => $s->where('users.id', $currentUser->id));
            });
        }

        // Фильтр по сотруднику (если выбран в модалке)
        if ($targetUserId && $mode !== 'my_tasks') {
            $taskQuery->where(function ($q) use ($targetUserId) {
                $q->whereHas('executors', fn($s) => $s->where('users.id', $targetUserId))
                    ->orWhereHas('responsibles', fn($s) => $s->where('users.id', $targetUserId));
            });
        }

        // Фильтры по Компании и Проекту
        if ($companyId) $taskQuery->where('company_id', $companyId);
        if ($projectId) $taskQuery->where('project_id', $projectId);

        $tasks = $taskQuery->get();

        foreach($tasks as $t) {
            $tasksCollection->push([
                'type' => 'Задача',
                'company' => $t->company->name ?? '—',
                'project' => $t->project->name ?? '—',
                'title' => $t->title,
                'status' => $t->completed ? 'Завершено' : ($t->status === 'in_work' ? 'В работе' : 'Новая'),
                'due_date' => $t->due_date,
                'executors' => $t->executors->pluck('name')->join(', '),
                'sort_company' => $t->company->name ?? 'zzzz', // для сортировки
                'sort_project' => $t->project->name ?? 'zzzz',
            ]);
        }

        // 2. ПОДЗАДАЧИ
        $subQuery = Subtask::with(['task.company', 'task.project', 'executors', 'responsibles'])
            ->withoutGlobalScope('not_completed');

        if ($mode === 'owner') {
            $myCompanyIds = Company::where('user_id', $currentUser->id)->pluck('id');
            $subQuery->whereHas('task', fn($q) => $q->whereIn('company_id', $myCompanyIds));
        } elseif ($mode === 'author') {
            $subQuery->where('creator_id', $currentUser->id);
        } elseif ($mode === 'my_tasks') {
            $subQuery->where(function ($q) use ($currentUser) {
                $q->whereHas('executors', fn($s) => $s->where('users.id', $currentUser->id))
                    ->orWhereHas('responsibles', fn($s) => $s->where('users.id', $currentUser->id));
            });
        }

        if ($targetUserId && $mode !== 'my_tasks') {
            $subQuery->where(function ($q) use ($targetUserId) {
                $q->whereHas('executors', fn($s) => $s->where('users.id', $targetUserId))
                    ->orWhereHas('responsibles', fn($s) => $s->where('users.id', $targetUserId));
            });
        }

        // Фильтры по Компании и Проекту (через родительскую задачу)
        if ($companyId) $subQuery->whereHas('task', fn($q) => $q->where('company_id', $companyId));
        if ($projectId) $subQuery->whereHas('task', fn($q) => $q->where('project_id', $projectId));

        $subtasks = $subQuery->get();

        foreach($subtasks as $s) {
            $tasksCollection->push([
                'type' => 'Подзадача',
                'company' => $s->task->company->name ?? '—',
                'project' => $s->task->project->name ?? '—',
                'title' => $s->title,
                'status' => $s->completed ? 'Завершено' : ($s->status === 'in_work' ? 'В работе' : 'Новая'),
                'due_date' => $s->due_date,
                'executors' => $s->executors->pluck('name')->join(', '),
                'sort_company' => $s->task->company->name ?? 'zzzz',
                'sort_project' => $s->task->project->name ?? 'zzzz',
            ]);
        }

        // СОРТИРОВКА: Компания -> Проект -> Дата
        $sorted = $tasksCollection->sortBy([
            ['sort_company', 'asc'],
            ['sort_project', 'asc'],
            ['type', 'asc'] // Сначала задачи, потом подзадачи (по алфавиту)
        ]);

        // ГЕНЕРАЦИЯ CSV
        $filename = "report_" . date('Y-m-d_H-i') . ".csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($sorted) {
            $file = fopen('php://output', 'w');
            // BOM для Excel (чтобы русский язык отображался корректно)
            fputs($file, "\xEF\xBB\xBF");

            // Заголовки
            fputcsv($file, ['Тип', 'Компания', 'Проект', 'Название',  'Срок', 'Исполнители'], ';');

            foreach ($sorted as $row) {
                fputcsv($file, [
                    $row['type'],
                    $row['company'],
                    $row['project'],
                    $row['title'],
                    $row['due_date'],
                    $row['executors']
                ], ';');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }


    public function completedByProject(Project $project)
    {
        $user = Auth::user();
        $userId = $user->id;

        $result = [
            'tasks' => [],
            'subtasks' => []
        ];

        // 1. Проверяем, имеет ли пользователь "Глобальный доступ" к этому проекту
        // (Владелец компании, Инициатор проекта или один из Менеджеров проекта)
        $hasFullAccess = ($project->company->user_id === $userId) ||
            ($project->initiator_id === $userId) ||
            ($project->managers()->where('users.id', $userId)->exists());

        // ========================
        // ЗАВЕРШЁННЫЕ ЗАДАЧИ
        // ========================
        $tasksQuery = Task::withoutGlobalScope('not_completed')
            ->where('project_id', $project->id)
            ->where('completed', 1);

        // Если нет полного доступа, фильтруем только те задачи, к которым юзер имеет отношение
        if (!$hasFullAccess) {
            $tasksQuery->where(function($q) use ($userId) {
                $q->where('creator_id', $userId) // Создатель
                ->orWhereHas('executors', fn($e) => $e->where('users.id', $userId)) // Исполнитель
                ->orWhereHas('responsibles', fn($r) => $r->where('users.id', $userId)) // Ответственный
                ->orWhereHas('watcherstask', fn($w) => $w->where('users.id', $userId)); //наблюдатель
            });
        }

        $tasks = $tasksQuery->with(['company:id,name'])
            ->orderByDesc('updated_at')
            ->get();

        foreach ($tasks as $task) {
            $result['tasks'][] = [
                'id' => $task->id,
                'title' => $task->title,
                'due_date' => $task->due_date,
                'company' => $task->company->name ?? '—',
                'project' => [ // Добавляем структуру проекта для фронтенда
                    'name' => $task->project->name ?? '—',
                    'company' => ['name' => $task->company->name ?? '—']
                ],
                'link' => "/tasks/{$task->id}",
            ];
        }

        // ========================
        // ЗАВЕРШЁННЫЕ ПОДЗАДАЧИ
        // ========================
        $subtasksQuery = Subtask::withoutGlobalScope('not_completed')
            ->where('completed', 1)
            ->whereHas('task', fn ($q) => $q->where('project_id', $project->id));

        // Если нет полного доступа, фильтруем только те подзадачи, где юзер участвует
        if (!$hasFullAccess) {
            $subtasksQuery->where(function($q) use ($userId) {
                $q->whereHas('executors', fn($e) => $e->where('users.id', $userId)) // Исполнитель подзадачи
                ->orWhereHas('responsibles', fn($r) => $r->where('users.id', $userId)) // Ответственный подзадачи
                // Или если он имеет отношение к родительской задаче, то может видеть и её подзадачи
                ->orWhereHas('task', function($parentTask) use ($userId) {
                    $parentTask->where('creator_id', $userId)
                        ->orWhereHas('executors', fn($e) => $e->where('users.id', $userId))
                        ->orWhereHas('responsibles', fn($r) => $r->where('users.id', $userId));
                });
            });
        }

        $subtasks = $subtasksQuery->with(['task:id,title'])
            ->orderByDesc('updated_at')
            ->get();

        foreach ($subtasks as $sub) {
            $result['subtasks'][] = [
                'id' => $sub->id,
                'title' => $sub->title,
                'task_title' => $sub->task->title,
                'due_date' => $sub->due_date,
                'link' => "/subtasks/{$sub->id}",
            ];
        }

        return response()->json($result);
    }



}
