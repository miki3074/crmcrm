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
   $managedProjects = Project::with('company', 'managers')
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
                 'managers' => $project->managers->map(fn($m) => [
            'id' => $m->id,
            'name' => $m->name,
        ]),
            ];
        });
        if (!$createdCompanies->contains('id', $company->id)) {
            $managedCompanies->push($company);
        }
    }

    // 3. Компании, где он исполнитель задач
    $executorTasks = Task::with(['project.company', 'project.manager'])
        ->whereHas('executors', fn($q) => $q->where('users.id', $userId))
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
$responsibleTasks = Task::with(['project.company', 'project.managers'])
    ->whereHas('responsibles', fn($q) => $q->where('users.id', $userId))
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


// $subtaskCompanies = Subtask::with(['task.project.company', 'task.project.manager'])
//     ->where('executor_id', $userId)
//     ->get()
//     ->groupBy(fn($subtask) => $subtask->task->project->company->id);

$subtaskCompanies = Subtask::query()
    // Берём только те подзадачи, у которых есть НЕ завершённая родительская задача
    ->whereHas('task') // этого достаточно, т.к. на Task висит глобальный скоуп not_completed
    ->with([
        // Важно: грузим те же связи через task
        'task.project.company',
        'task.project.managers',
    ])
    ->whereHas('executors', fn($q) => $q->where('users.id', $userId))
    ->get();

   $memberCompanies = Company::with('projects')
    ->whereHas('users', fn($q) => $q->where('user_id', $userId))
    ->get();


$subtaskCompanies = $subtaskCompanies
    ->filter(fn($s) => $s->task && $s->task->project && $s->task->project->company) // защита
    ->groupBy(fn($s) => $s->task->project->company->id)
    ->map(function ($subtasks, $companyId) {
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
             ->concat($memberCompanies) 
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

    // Загружаем проекты с нужными связями
    $company->load([
        'projects' => function ($q) {
            $q->with([
                'managers:id,name',
                'tasks.executors:id,name',
                'tasks.responsibles:id,name',
                'tasks.subtasks.executors:id,name',
                'tasks.subtasks.responsibles:id,name',
            ]);
        }
    ]);

    // Фильтруем проекты по доступу пользователя
    $company->projects = $company->projects->filter(function ($project) use ($userId, $company) {
        if ($company->user_id === $userId) return true;
        if ($project->managers->contains('id', $userId)) return true;
        if ($project->tasks->contains(fn($t) => $t->executors->contains('id', $userId))) return true;
        if ($project->tasks->contains(fn($t) => $t->responsibles->contains('id', $userId))) return true;

        // подзадачи
        if ($project->tasks->contains(fn($t) => $t->subtasks->contains(fn($s) => $s->executors->contains('id', $userId)))) return true;
        if ($project->tasks->contains(fn($t) => $t->subtasks->contains(fn($s) => $s->responsibles->contains('id', $userId)))) return true;

        return false;
    })->values();

    // Ответ JSON
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
                'managers' => $project->managers->map(fn($m) => [
                    'id' => $m->id,
                    'name' => $m->name,
                ]),
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

//   public function employees(\App\Models\Company $company)
// {
//     $user = auth()->user();

//     // Разрешим видеть сотрудников владельцу компании и его сотрудникам
//     abort_unless(
//         $user->id === $company->user_id || $user->created_by === $company->user_id,
//         403
//     );

//     // владелец + все пользователи, созданные владельцем
//     $owner = \App\Models\User::select('id','name','email')
//         ->where('id', $company->user_id);

//     $staff = \App\Models\User::select('id','name','email')
//         ->where('created_by', $company->user_id);

//     $employees = $owner->union($staff)->get();

//     return response()->json($employees);
// }

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
        ->latest('id')->take(8)
        ->get(['id','name','company_id']);


        // Задачи, где я наблюдатель
$watchingTasks = Task::with([
        'project:id,name,company_id',
        'project.company:id,name'
    ])
    ->whereHas('watchers', function ($q) use ($user) {
        $q->where('users.id', $user->id);
    })
    ->orderByRaw('due_date IS NULL, due_date ASC')
    ->take(12)
    ->get(['id','title','priority','progress','start_date','due_date','project_id']);


    // Задачи, где я исполнитель
    $myTasks = Task::with([
            'project:id,name,company_id',
            'project.company:id,name'
        ])
        ->whereHas('executors', fn($q) => $q->where('users.id', $user->id))
        ->orderByRaw('due_date IS NULL, due_date ASC')
        ->take(12)
        ->get(['id','title','priority','progress','start_date','due_date','project_id']);

    // Задачи, где я ответственный
    $responsibleTasks = Task::with([
            'project:id,name,company_id',
            'project.company:id,name'
        ])
        ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
        ->orderByRaw('due_date IS NULL, due_date ASC')
        ->take(12)
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
        ->take(12)
        ->get(['id','title','start_date','due_date','task_id']);

    // Подзадачи, где я ответственный
    $responsibleSubtasks = Subtask::with([
            'task:id,title,project_id',
            'task.project:id,name,company_id',
            'task.project.company:id,name'
        ])
        ->whereHas('responsibles', fn($q) => $q->where('users.id', $user->id))
        ->orderByRaw('due_date IS NULL, due_date ASC')
        ->take(12)
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
        ->latest('id')->take(8)
        ->get(['id','title','project_id','responsible_id']);

    // Срезы по срокам (берём только из задач)
    $dueToday = $allTasks->filter(fn($t) =>
        !empty($t->due_date) && Carbon::parse($t->due_date)->isSameDay($today)
    )->values();

    $overdue = $allTasks->filter(fn($t) =>
        !empty($t->due_date) && Carbon::parse($t->due_date)->lt($today)
    )->values();

    return response()->json([
        'managing_projects'       => $managingProjects,
        'all_tasks'               => $allTasks,        // ✅ объединённый список задач
        'all_subtasks'            => $allSubtasks,     // ✅ объединённый список подзадач
        'responsible_subprojects' => $responsibleSubprojects,
        'due_today'               => $dueToday,
        'overdue'                 => $overdue,
        'watching_tasks'          => $watchingTasks,
    ]);
}





}
