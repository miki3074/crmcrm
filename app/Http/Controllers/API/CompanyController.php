<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\Project;
use App\Models\Task;
use App\Models\Subtask;

use App\Models\Subproject;

use Carbon\Carbon;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{


    public function index()
    {
        $userId = auth()->id();

        // Оптимизация: выбираем только нужные поля задач, чтобы не грузить лишнее
        $taskSelect = function ($q) {
            $q->select('id', 'project_id', 'title', 'description', 'priority', 'status', 'due_date', 'start_date');
        };

        // 1. Компании, созданные пользователем
        $createdCompanies = Company::with([
            'projects' => function ($q) use ($taskSelect) {
                $q->with(['manager:id,name', 'tasks' => $taskSelect]);
            }
        ])->where('user_id', $userId)->get();

        // 2. Компании, где он руководитель проектов
        $managedProjects = Project::with(['company', 'managers', 'tasks' => $taskSelect])
            ->whereHas('managers', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->get()
            ->groupBy('company_id');

        $managedCompanies = collect();
        foreach ($managedProjects as $companyId => $projects) {
            $company = $projects->first()->company;
            $company->projects = $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date, // Нужно для Vue (daysLeft)
                    'duration_days' => $project->duration_days ?? 30, // Нужно для Vue
                    'managers' => $project->managers->map(fn($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                    ]),
                    'tasks' => $project->tasks // <-- ДОБАВЛЕНО
                ];
            });
            if (!$createdCompanies->contains('id', $company->id)) {
                $managedCompanies->push($company);
            }
        }

        // 3. Компании, где он исполнитель задач
        // Здесь мы показываем ТОЛЬКО те задачи, где он исполнитель, или весь проект?
        // Обычно в списке задач проекта показывают все, но если логика строгая, оставляем как есть, но формируем структуру.
        $executorTasks = Task::with(['project.company', 'project.manager'])
            ->whereHas('executors', fn($q) => $q->where('users.id', $userId))
            ->get();

        $groupedByCompany = $executorTasks->groupBy(fn($task) => $task->project->company->id);
        $executorCompanies = $groupedByCompany->map(function ($tasks, $companyId) {
            $company = $tasks->first()->project->company;
            $projects = $tasks->groupBy('project_id')->map(function ($projectTasks) {
                $project = $projectTasks->first()->project;
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date,
                    'duration_days' => $project->duration_days,
                    'manager' => [
                        'id' => $project->manager->id ?? null,
                        'name' => $project->manager->name ?? '—',
                    ],
                    // ВАЖНО: Превращаем коллекцию задач в массив для Vue
                    'tasks' => $projectTasks->map(fn($t) => [
                        'id' => $t->id,
                        'title' => $t->title,
                        'description' => $t->description,
                        'priority' => $t->priority,
                        'status' => $t->status,
                        'due_date' => $t->due_date,
                    ])->values()
                ];
            })->values();
            return [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
                'projects' => $projects
            ];
        })->values();

        // 4. Компании, где он ответственный по задачам
        $responsibleTasks = Task::with(['project.company', 'project.managers'])
            ->whereHas('responsibles', fn($q) => $q->where('users.id', $userId))
            ->get();

        $responsibleGrouped = $responsibleTasks->groupBy(fn($task) => $task->project->company->id);
        $responsibleCompanies = $responsibleGrouped->map(function ($tasks, $companyId) {
            $company = $tasks->first()->project->company;
            $projects = $tasks->groupBy('project_id')->map(function ($projectTasks) {
                $project = $projectTasks->first()->project;
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date,
                    'duration_days' => $project->duration_days,
                    'manager' => [
                        'id' => $project->manager->id ?? null,
                        'name' => $project->manager->name ?? '—',
                    ],
                    'tasks' => $projectTasks->map(fn($t) => [
                        'id' => $t->id,
                        'title' => $t->title,
                        'description' => $t->description,
                        'priority' => $t->priority,
                        'status' => $t->status,
                        'due_date' => $t->due_date,
                    ])->values()
                ];
            })->values();

            return [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
                'projects' => $projects
            ];
        })->values();


        // 6. Компании, где пользователь наблюдатель проекта
        $watcherProjects = Project::with(['company', 'managers', 'watchers', 'tasks' => $taskSelect]) // <-- Added tasks
        ->whereHas('watchers', function ($q) use ($userId) {
            $q->where('project_watchers.user_id', $userId);
        })
            ->get()
            ->groupBy('company_id');

        $watcherCompanies = collect();

        foreach ($watcherProjects as $companyId => $projects) {
            $company = $projects->first()->company;

            $company->projects = $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date,
                    'duration_days' => $project->duration_days,
                    'managers' => $project->managers->map(fn($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                    ]),
                    'is_watcher' => true,
                    'tasks' => $project->tasks // <-- ДОБАВЛЕНО
                ];
            });

            $watcherCompanies->push($company);
        }


        // 7. Компании, где пользователь исполнитель проекта
        $executorProjects = Project::with(['company', 'managers', 'executors', 'tasks' => $taskSelect]) // <-- Added tasks
        ->whereHas('executors', function ($q) use ($userId) {
            $q->where('project_executors.user_id', $userId);
        })
            ->get()
            ->groupBy('company_id');

        $projectExecutorCompanies = collect();

        foreach ($executorProjects as $companyId => $projects) {
            $company = $projects->first()->company;

            $company->projects = $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date,
                    'duration_days' => $project->duration_days,
                    'managers' => $project->managers->map(fn($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                    ]),
                    'is_project_executor' => true,
                    'tasks' => $project->tasks // <-- ДОБАВЛЕНО
                ];
            });

            $projectExecutorCompanies->push($company);
        }


        // 5. Компании, где он исполнитель подзадач
        $subtaskCompanies = Subtask::query()
            ->whereHas('task')
            ->with([
                'task.project.company',
                'task.project.managers',
            ])
            ->whereHas('executors', fn($q) => $q->where('users.id', $userId))
            ->get();

        $memberCompanies = Company::with('projects.tasks') // <-- Added tasks if needed here too
        ->whereHas('users', fn($q) => $q->where('user_id', $userId))
            ->get();


        $subtaskCompanies = $subtaskCompanies
            ->filter(fn($s) => $s->task && $s->task->project && $s->task->project->company)
            ->groupBy(fn($s) => $s->task->project->company->id)
            ->map(function ($subtasks, $companyId) {
                $company = $subtasks->first()->task->project->company;
                $projects = $subtasks->groupBy(fn($s) => $s->task->project_id)->map(function ($subtasks) {
                    $project = $subtasks->first()->task->project;

                    // Группируем подзадачи по родительской задаче
                    $tasks = $subtasks->groupBy('task_id')->map(function ($subs) {
                        $task = $subs->first()->task;
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => $task->description,
                            // ВАЖНО: Добавляем поля статуса для таблицы
                            'priority' => $task->priority,
                            'status' => $task->status,
                            'due_date' => $task->due_date,

                            'subtasks' => $subs->map(function ($s) {
                                return [
                                    'id' => $s->id,
                                    'title' => $s->title,
                                    'start_date' => $s->start_date,
                                    'due_date' => $s->due_date,
                                    'executors' => $s->executors->map(fn($u) => [
                                        'id' => $u->id,
                                        'name' => $u->name,
                                    ]),
                                    'responsibles' => $s->responsibles->map(fn($u) => [
                                        'id' => $u->id,
                                        'name' => $u->name,
                                    ]),
                                    'creator' => [
                                        'id' => $s->creator->id,
                                        'name' => $s->creator->name,
                                    ]
                                ];
                            })->values()
                        ];
                    })->values();

                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'start_date' => $project->start_date,
                        'duration_days' => $project->duration_days,
                        'manager' => [
                            'id' => $project->manager->id ?? null,
                            'name' => $project->manager->name ?? '—',
                        ],
                        'tasks' => $tasks,
                    ];
                })->values();

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo' => $company->logo,
                    'projects' => $projects,
                ];
            })->values();

        $allCompanies = $createdCompanies
            ->concat($managedCompanies)
            ->concat($executorCompanies)
            ->concat($responsibleCompanies)
            ->concat($subtaskCompanies)
            ->concat($memberCompanies)
            ->concat($watcherCompanies)
            ->concat($projectExecutorCompanies)
            ->unique('id')
            ->values();

        // !ВАЖНО:
        // Если ваш Vue компонент ждет массив ПРОЕКТОВ (projects), а не компаний,
        // нужно раскомментировать код ниже.
        // Если компонент умеет доставать проекты из компаний сам - оставьте как есть.

        /*
        $flatProjects = $allCompanies->flatMap(function($company) {
            return $company['projects'] ?? $company->projects;
        })->unique('id')->values();

        return response()->json($flatProjects);
        */

        return response()->json($allCompanies);
    }





    public function store(Request $request)
    {
        $userId = Auth::id();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                // 👇 Правило уникальности с условием
                Rule::unique('companies', 'name')->where(function ($query) use ($userId) {
                    return $query->where('user_id', $userId);
                }),
            ],
            'logo' => 'nullable|image|max:2048',
        ], [
            // 👇 Кастомное сообщение об ошибке
            'name.unique' => 'У вас уже создана компания с таким названием.',
        ]);

        // ... Ваш код сохранения дальше без изменений
        $path = null;
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
        }

        $company = Company::create([
            'user_id' => $userId,
            'name' => $request->name,
            'logo' => $path,
        ]);

        return response()->json($company, 201);
    }


    public function show(Company $company)
    {
        $this->authorize('view', $company);
        $userId = auth()->id();

        // 1. Загрузка данных
        $company->load([
            'projects' => function ($q) {
                $q->with([
                    'managers:id,name',
                    'executors:id,name',
                    'watchers:id,name',
                    // Грузим задачи
                    'tasks' => function($t) {
                        $t->orderBy('created_at', 'desc');
                    },
                    // Важно: грузим связи, нужные для проверки прав доступа к задаче
                    'tasks.executors:id,name',
                    'tasks.responsibles:id,name',
                    'tasks.subtasks.executors:id,name',
                    'tasks.subtasks.responsibles:id,name',
                    // Если есть creator, лучше подгрузить, но обычно id есть в самой таблице tasks
                ]);
            }
        ]);

        // 2. Фильтрация списка ПРОЕКТОВ (оставляем как было, чтобы скрыть лишние проекты)
        $company->projects = $company->projects->filter(function ($project) use ($userId, $company) {
            // ... (Ваша логика фильтрации проектов остается без изменений) ...
            if ($company->user_id === $userId) return true;
            if ($project->initiator_id === $userId) return true;
            if ($project->managers->contains('id', $userId)) return true;
            if ($project->executors->contains('id', $userId)) return true;
            if ($project->watchers->contains('id', $userId)) return true;
            if ($project->tasks->contains(fn($t) => $t->executors->contains('id', $userId))) return true;
            if ($project->tasks->contains(fn($t) => $t->responsibles->contains('id', $userId))) return true;
            if ($project->tasks->contains(fn($t) =>
            $t->subtasks->contains(fn($s) => $s->executors->contains('id', $userId))
            )) return true;
            if ($project->tasks->contains(fn($t) =>
            $t->subtasks->contains(fn($s) => $s->responsibles->contains('id', $userId))
            )) return true;

            return false;
        })->values();

        // 3. Формируем ответ и ФИЛЬТРУЕМ ЗАДАЧИ
        return response()->json([
            'id' => $company->id,
            'name' => $company->name,
            'logo' => $company->logo,
            'user_id' => $company->user_id,

            'projects' => $company->projects->map(function ($project) use ($userId, $company) {

                // --- ЛОГИКА ДОСТУПА К ЗАДАЧАМ (как в ProjectController) ---

                // Пользователь имеет ПОЛНЫЙ доступ, если он:
                // 1. Владелец компании
                // 2. Инициатор проекта
                // 3. Менеджер проекта
                $hasFullAccess = (
                    $company->user_id === $userId ||
                    $project->initiator_id === $userId ||
                    $project->managers->contains('id', $userId)
                );

                // Фильтруем коллекцию задач
                $filteredTasks = $project->tasks->filter(function ($task) use ($userId, $hasFullAccess) {
                    // Если босс/менеджер — видит всё
                    if ($hasFullAccess) return true;

                    // Иначе проверяем участие в конкретной задаче
                    $isCreator = $task->creator_id === $userId; // Убедитесь, что поле creator_id есть в модели
                    $isExecutor = $task->executors->contains('id', $userId);
                    $isResponsible = $task->responsibles->contains('id', $userId);

                    // (Опционально) Если вы хотите, чтобы наблюдатели проекта видели все задачи,
                    // добавьте проверку $project->watchers->contains(...) в $hasFullAccess.
                    // Но судя по ProjectController, вы хотите строгую фильтрацию.

                    return $isCreator || $isExecutor || $isResponsible;
                });

                // -----------------------------------------------------------

                $endDate = null;
                if ($project->start_date && $project->duration_days) {
                    $endDate = \Carbon\Carbon::parse($project->start_date)
                        ->addDays($project->duration_days)
                        ->format('Y-m-d');
                }

                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date,
                    'duration_days' => $project->duration_days,
                    'end_date' => $endDate,

                    'chart' => [
                        'name' => $project->name,
                        'start' => $project->start_date,
                        'end' => $endDate,
                        'duration' => $project->duration_days,
                    ],

                    'managers' => $project->managers->map(fn($m) => [
                        'id' => $m->id,
                        'name' => $m->name,
                    ]),

                    'executors' => $project->executors->map(fn($e) => [
                        'id' => $e->id,
                        'name' => $e->name,
                    ]),

                    // Используем отфильтрованные задачи ($filteredTasks) вместо всех ($project->tasks)
                    'tasks' => $filteredTasks->values()->map(fn($t) => [
                        'id' => $t->id,
                        'title' => $t->title,
                        'description' => $t->description,
                        'priority' => $t->priority,
                        'status' => $t->status,
                        'due_date' => $t->due_date,
                        // Добавляем ответственных для отображения аватарок в списке
                        'responsibles' => $t->responsibles->map(fn($r) => ['id' => $r->id, 'name' => $r->name]),
                    ]),

                    'is_manager' => $project->managers->contains('id', $userId),
                    'is_executor' => $project->executors->contains('id', $userId),
                    'is_watcher' => $project->watchers->contains('id', $userId),
                ];
            }),
        ]);
    }

    public function companiesWhereUserIsManager()
    {
        $userId = auth()->id();

        // Получаем проекты с привязкой к компаниям
        $projects = \App\Models\Project::with('company')
            ->whereHas('managers', function ($q) use ($userId) {
        $q->where('users.id', $userId);
    })
    ->get();

        // Группируем проекты по company_id
        $grouped = $projects->groupBy('company_id')->map(function ($projects) {
            $company = $projects->first()->company;

            return [
                'id' => $company->id,
                'name' => $company->name,
                'logo' => $company->logo,
                'projects' => $projects->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                    ];
                })->values()
            ];
        })->values();

        return response()->json($grouped);
    }

public function employees(\App\Models\Company $company)
{
    $authUser = auth()->user();

    $isOwner   = $company->user_id === $authUser->id;
    $isManager = $company->users()
        ->where('users.id', $authUser->id)
        ->where('company_user.role', 'manager')
        ->exists();

    abort_unless($isOwner || $isManager, 403);

    $staff = $company->users()
        ->select('users.id','users.name','users.email','company_user.role')
        ->get();

    $owner = \App\Models\User::select('id','name','email')->find($company->user_id);
    if ($owner) {
        $owner->role = 'owner';
        if (!$staff->contains('id', $owner->id)) {
            $staff->prepend($owner); // добавляем в начало списка
        }
    }

    return response()->json($staff);
}


public function summary(Request $request)
{
    $user = $request->user();
    $today = Carbon::today();

    // Проекты, где пользователь — руководитель
    $managingProjects = Project::with(['company:id,name'])
        ->withCount('tasks')
        ->whereHas('managers', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })
        ->latest('id')
        // ->take(8)
        ->get(['id','name','company_id']);


        // Задачи, где я наблюдатель
$watchingTasks = Task::with([
        'project:id,name,company_id',
        'project.company:id,name'
    ])
    ->whereHas('watcherstask', function ($q) use ($user) {
        $q->where('users.id', $user->id);
    })
    ->orderByRaw('due_date IS NULL, due_date ASC')
    // ->take(12)
    ->get(['id','title','priority','progress','start_date','due_date','project_id']);


    // Задачи, где я исполнитель
    $myTasks = Task::with([
            'project:id,name,company_id',
            'project.company:id,name'
        ])
        ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
        ->orderByRaw('due_date IS NULL, due_date ASC')
        // ->take(12)
        ->get(['id','title','priority','progress','start_date','due_date','project_id']);

    // Задачи, где я ответственный
    $responsibleTasks = Task::with([
            'project:id,name,company_id',
            'project.company:id,name'
        ])
        ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
        ->orderByRaw('due_date IS NULL, due_date ASC')
        // ->take(12)
        ->get(['id','title','priority','progress','start_date','due_date','project_id']);

    // 👇 объединяем задачи (исполнитель + ответственный)
    $allTasks = $myTasks->concat($responsibleTasks)
        ->unique('id')
        ->values();

    // Подзадачи, где я исполнитель
    $mySubtasks = Subtask::with([
            'task:id,title,project_id',
            'task.project:id,name,company_id',
            'task.project.company:id,name'
        ])
        ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
        ->orderByRaw('due_date IS NULL, due_date ASC')
        // ->take(12)
        ->get(['id','title','start_date','due_date','task_id']);

    // Подзадачи, где я ответственный
    $responsibleSubtasks = Subtask::with([
            'task:id,title,project_id',
            'task.project:id,name,company_id',
            'task.project.company:id,name'
        ])
        ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
        ->orderByRaw('due_date IS NULL, due_date ASC')
        // ->take(12)
        ->get(['id','title','start_date','due_date','task_id']);

    // 👇 объединяем подзадачи
    $allSubtasks = $mySubtasks->concat($responsibleSubtasks)
        ->unique('id')
        ->values();

    // Подпроекты, где я ответственный
    $responsibleSubprojects = Subproject::with([
            'project:id,name,company_id',
            'project.company:id,name'
        ])
        ->withCount(['tasks as open_tasks_count' => fn($q) => $q->where('completed', false)])
        ->where('responsible_id', $user->id)
        ->latest('id')
        // ->take(8)
        ->get(['id','title','project_id','responsible_id']);


// 📦 Проекты, где я наблюдатель
$watchingProjects = Project::with([
        'company:id,name',
        'managers:id,name',
    ])
    ->whereHas('watchers', function ($q) use ($user) {
        $q->where('users.id', $user->id);
    })
    ->latest('id')
    // ->take(8)
    ->get(['id','name','company_id','initiator_id']);



    // Срезы по срокам (берём только из задач)
    $dueToday = $allTasks->filter(fn($t) =>
        !empty($t->due_date) && Carbon::parse($t->due_date)->isSameDay($today)
    )->values();

    $overdue = $allTasks->filter(fn($t) =>
        !empty($t->due_date) && Carbon::parse($t->due_date)->lt($today)
    )->values();


// === Группировка подзадач ===
$groupedSubtasks = [];

foreach ($allSubtasks as $sub) {
    $company = $sub->task->project->company->name ?? 'Без компании';
    $project = $sub->task->project->name ?? 'Без проекта';
    $task    = $sub->task->title ?? 'Без задачи';

    $groupedSubtasks[$company][$project][$task][] = [
        'id' => $sub->id,
        'title' => $sub->title,
        'start_date' => $sub->start_date,
        'due_date' => $sub->due_date,
        'task_id' => $sub->task_id,
    ];
}



    return response()->json([
        'managing_projects'       => $managingProjects,
        'all_tasks'               => $allTasks,
        'all_subtasks'            => $groupedSubtasks,
        // 'all_subtasks'            => $allSubtasks,
        'responsible_subprojects' => $responsibleSubprojects,
        'due_today'               => $dueToday,
        'overdue'                 => $overdue,
        'watching_tasks'          => $watchingTasks,
        'watching_projects'       => $watchingProjects,
    ]);
}



public function destroy(Request $request, \App\Models\Company $company)
{
    // ✅ Проверяем пароль
    $request->validate([
        'password' => 'required|string',
    ]);

    if (!Hash::check($request->password, $request->user()->password)) {
        return response()->json(['message' => 'Неверный пароль. Удаление отклонено.'], 403);
    }

    // ✅ Разрешение: только владелец компании
    $this->authorize('delete', $company);

    // удаляем все связанные данные (твой код остаётся)
    foreach ($company->projects as $project) {
        foreach ($project->tasks as $task) {
            foreach ($task->files as $file) {
                if (\Storage::disk('public')->exists($file->file_path)) {
                    \Storage::disk('public')->delete($file->file_path);
                }
                $file->delete();
            }

            foreach ($task->subtasks as $subtask) {
                $subtask->delete();
            }

            $task->delete();
        }

        if (method_exists($project, 'subprojects')) {
            foreach ($project->subprojects as $sp) {
                $sp->delete();
            }
        }

        $project->delete();
    }

    $company->delete();

    return response()->json(['message' => 'Компания и все связанные данные удалены.']);
}


 public function members(Company $company)
    {
        $user = auth()->user();

        // Только владелец компании может видеть участников
        if ($user->id !== $company->user_id) {
            abort(403, 'Доступ запрещён');
        }

        $members = $company->users()
            ->withPivot(['role', 'created_by'])
            ->select('users.id', 'users.name', 'users.email')
            ->orderByRaw("FIELD(company_user.role, 'owner', 'manager', 'employee') ASC")
            ->get();

        return response()->json($members);
    }
}
