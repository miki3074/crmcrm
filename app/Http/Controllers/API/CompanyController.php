<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth; 
use App\Models\Company; 
use App\Models\Project;
use App\Models\Task;
use App\Models\Subtask;

class CompanyController extends Controller
{
   
public function index()
{
    $userId = auth()->id();

    // 1. Компании, созданные пользователем
   $createdCompanies = Company::with([
    'projects' => function ($q) {
        $q->with('manager:id,name'); // просто грузим все проекты этой компании
    }
])->where('user_id', $userId)->get();

    // 2. Компании, где он руководитель проектов
    $managedProjects = Project::with('company')
        ->where('manager_id', $userId)
        ->get()
        ->groupBy('company_id');

    $managedCompanies = collect();
    foreach ($managedProjects as $companyId => $projects) {
        $company = $projects->first()->company;
        $company->projects = $projects->map(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'manager' => [
                    'id' => $project->manager->id,
                    'name' => $project->manager->name,
                ],
            ];
        });
        if (!$createdCompanies->contains('id', $company->id)) {
            $managedCompanies->push($company);
        }
    }

    // 3. Компании, где он исполнитель задач
    $executorTasks = Task::with(['project.company', 'project.manager'])
        ->where('executor_id', $userId)
        ->get();

    $groupedByCompany = $executorTasks->groupBy(fn($task) => $task->project->company->id);
    $executorCompanies = $groupedByCompany->map(function ($tasks, $companyId) {
        $company = $tasks->first()->project->company;
        $projects = $tasks->groupBy('project_id')->map(function ($tasks) {
            $project = $tasks->first()->project;
            return [
                'id' => $project->id,
                'name' => $project->name,
                'manager' => [
                    'id' => $project->manager->id ?? null,
                    'name' => $project->manager->name ?? '—',
                ],
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
$responsibleTasks = Task::with(['project.company', 'project.manager'])
    ->where('responsible_id', $userId)
    ->get();

$responsibleGrouped = $responsibleTasks->groupBy(fn($task) => $task->project->company->id);
$responsibleCompanies = $responsibleGrouped->map(function ($tasks, $companyId) {
    $company = $tasks->first()->project->company;
    $projects = $tasks->groupBy('project_id')->map(function ($tasks) {
        $project = $tasks->first()->project;
        return [
            'id' => $project->id,
            'name' => $project->name,
            'manager' => [
                'id' => $project->manager->id ?? null,
                'name' => $project->manager->name ?? '—',
            ],
        ];
    })->values();

    return [
        'id' => $company->id,
        'name' => $company->name,
        'logo' => $company->logo,
        'projects' => $projects
    ];
})->values();


// 5. Компании, где он исполнитель подзадач


$subtaskCompanies = Subtask::with(['task.project.company', 'task.project.manager'])
    ->where('executor_id', $userId)
    ->get()
    ->groupBy(fn($subtask) => $subtask->task->project->company->id);

$subtaskCompanies = $subtaskCompanies->map(function ($subtasks, $companyId) {
    $company = $subtasks->first()->task->project->company;
    $projects = $subtasks->groupBy(fn($s) => $s->task->project_id)->map(function ($subtasks) {
        $project = $subtasks->first()->task->project;
        $tasks = $subtasks->groupBy('task_id')->map(function ($subs) {
            $task = $subs->first()->task;
            return [
                'id' => $task->id,
                'title' => $task->title,
                'subtasks' => $subs->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'title' => $s->title,
                        'start_date' => $s->start_date,
                        'due_date' => $s->due_date,
                        'executor' => [
                            'id' => $s->executor->id,
                            'name' => $s->executor->name,
                        ],
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

    return response()->json(
        $createdCompanies
            ->concat($managedCompanies)
            ->concat($executorCompanies)
            ->concat($responsibleCompanies)
            ->concat($subtaskCompanies)
            ->unique('id')
            ->values()
    );
}


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
        }

        $company = Company::create([
            'user_id' => Auth::id(),
            'name' => $request->name,
            'logo' => $path,
        ]);

        return response()->json($company, 201);
    }

  
public function show(Company $company)
{
    $this->authorize('view', $company);
    $userId = auth()->id();

    $company->load([
        'projects' => function ($q) use ($userId) {
            $q->with([
                'manager:id,name',
                'tasks.subtasks' => function ($s) use ($userId) {
                    $s->where('executor_id', $userId);
                },
                'tasks.executor:id,name',
                'tasks.responsible:id,name'
            ]);
        }
    ]);

    $company->projects = $company->projects->filter(function ($project) use ($userId, $company) {
    // 1. Если пользователь — основатель компании → показываем ВСЕ проекты
    if ($company->user_id === $userId) {
        return true;
    }

    // 2. Если он менеджер проекта
    if ($project->manager_id === $userId) {
        return true;
    }

    // 3. Есть задачи, где он исполнитель или ответственный
    if ($project->tasks->where('executor_id', $userId)->isNotEmpty()) {
        return true;
    }
    if ($project->tasks->where('responsible_id', $userId)->isNotEmpty()) {
        return true;
    }

    // 4. Есть подзадачи, где он исполнитель
    foreach ($project->tasks as $task) {
        if ($task->subtasks->isNotEmpty()) {
            return true;
        }
    }

    return false;
})->values();

    return response()->json([
        'id' => $company->id,
        'name' => $company->name,
        'logo' => $company->logo,
        'user_id' => $company->user_id,
        'projects' => $company->projects->map(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'start_date' => $project->start_date,
                'duration_days' => $project->duration_days,
                'manager' => [
                    'id' => $project->manager->id ?? null,
                    'name' => $project->manager->name ?? '—',
                ],
            ];
        }),
    ]);
}


    
    public function companiesWhereUserIsManager()
    {
        $userId = auth()->id();

        // Получаем проекты с привязкой к компаниям
        $projects = \App\Models\Project::with('company')
            ->where('manager_id', $userId)
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
    $user = auth()->user();

    // Разрешим видеть сотрудников владельцу компании и его сотрудникам
    abort_unless(
        $user->id === $company->user_id || $user->created_by === $company->user_id,
        403
    );

    // владелец + все пользователи, созданные владельцем
    $owner = \App\Models\User::select('id','name','email')
        ->where('id', $company->user_id);

    $staff = \App\Models\User::select('id','name','email')
        ->where('created_by', $company->user_id);

    $employees = $owner->union($staff)->get();

    return response()->json($employees);
}

}
