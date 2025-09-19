<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Models\Company;

class ProjectController extends Controller
{
//        public function store(Request $request)
// {
//     $request->validate([
//         'name' => 'required|string|max:255',
//         'manager_id' => 'required|exists:users,id',
//         'start_date' => 'required|date',
//         'duration_days' => 'required|integer|min:1',
//         'company_id' => 'required|exists:companies,id',
//     ]);

//     $company = \App\Models\Company::findOrFail($request->company_id);

//     // Проверяем, что текущий пользователь — владелец компании
//     if ($company->user_id !== auth()->id()) {
//         return response()->json(['message' => 'Только владелец компании может создавать проекты'], 403);
//     }

//     $project = Project::create([
//         'name' => $request->name,
//         'manager_id' => $request->manager_id,
//         'start_date' => $request->start_date,
//         'duration_days' => $request->duration_days,
//         'company_id' => $request->company_id,
//         'initiator_id' => auth()->id(),
//     ]);

//     return response()->json($project, 201);
// }

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'manager_ids' => 'required|array|min:1',
        'manager_ids.*' => 'exists:users,id',
        'start_date' => 'required|date',
        'duration_days' => 'required|integer|min:1',
        'company_id' => 'required|exists:companies,id',
    ]);

    $company = \App\Models\Company::findOrFail($request->company_id);

    if ($company->user_id !== auth()->id()) {
        return response()->json(['message' => 'Только владелец компании может создавать проекты'], 403);
    }

    $project = Project::create([
        'name' => $request->name,
        'start_date' => $request->start_date,
        'duration_days' => $request->duration_days,
        'company_id' => $request->company_id,
        'initiator_id' => auth()->id(),
    ]);

    // прикрепляем руководителей
    $project->managers()->attach($request->manager_ids);

    foreach ($request->manager_ids as $userId) {
        $user = \App\Models\User::find($userId);
        if ($user && $user->telegram_chat_id) {
            \App\Services\TelegramService::sendMessage(
                $user->telegram_chat_id,
                "📢 Вы назначены руководителем проекта: <b>{$project->name}</b>\n".
                "Компания: {$company->name}\n".
                "Дата начала: {$project->start_date}\n".
                "Длительность: {$project->duration_days} дней"
            );
        }
    }

    return response()->json($project->load('managers'), 201);
}




public function show($id)
{
    $project = Project::with([
        'managers:id,name',
        'company:id,name,user_id',
        'initiator:id,name',
        'subprojects.responsibles:id,name',
        'tasks' => function ($q) {
            $q->with([
                'creator:id,name',
                'executors:id,name',     // many-to-many исполнители
                'responsibles:id,name',  // many-to-many ответственные
                'files:id,task_id,file_path',
            ]);
        }
    ])->findOrFail($id);

    $user = auth()->user();

    foreach ($project->tasks as $task) {
        // если пользователь владелец компании → видит все файлы
        if ($user->id === $project->company->user_id) {
            continue;
        }

        // если пользователь создатель задачи
        if ($user->id === $task->creator_id) {
            continue;
        }

        // если пользователь исполнитель
        if ($task->executors->contains('id', $user->id)) {
            continue;
        }

        // если пользователь ответственный
        if ($task->responsibles->contains('id', $user->id)) {
            continue;
        }

        // иначе скрываем файлы
        $task->setRelation('files', collect([]));
    }

    return response()->json($project);
}



//             public function employees(Project $project)
// {
//     $company = $project->company;

//     // Все сотрудники компании (созданные этим пользователем)
//     $employees = \App\Models\User::where('created_by', $company->user_id)
//         ->orWhere('id', $company->user_id) // Добавляем владельца компании
//         ->get(['id', 'name']);

//     return response()->json($employees);
// }

public function employees(Project $project)
{
    $company = $project->company;

    // 1. Пользователи, созданные владельцем компании + сам владелец
    $createdUsers = \App\Models\User::where('created_by', $company->user_id)
        ->orWhere('id', $company->user_id)
        ->get(['id', 'name', 'email']);

    // 2. Пользователи, прикрепленные через pivot company_user
    $attachedUsers = $company->users()
        ->get(['users.id', 'users.name', 'users.email']); // явно указываем таблицу

    // 3. Объединяем коллекции, исключаем дубликаты по id
    $employees = $createdUsers->merge($attachedUsers)->unique('id')->values();

    return response()->json($employees);
}




public function updateBudget(Request $request, Project $project)
{
    $this->authorize('updateBudget', $project);

    $validated = $request->validate([
        'budget' => 'required|numeric|min:0',
    ]);

    $project->update(['budget' => $validated['budget']]);

    return response()->json(['message' => 'Бюджет обновлён', 'project' => $project]);
}

public function updateDescription(Request $request, Project $project)
{
    $this->authorize('updateDescription', $project);

    $validated = $request->validate([
        'description' => 'required|string|min:3',
    ]);

    $project->update(['description' => $validated['description']]);

    return response()->json(['message' => 'Описание обновлено', 'project' => $project]);
}


public function updateName(Request $request, Project $project)
{
    $this->authorize('update', $project);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ]);

    $project->update(['name' => $validated['name']]);

    return response()->json([
        'message' => 'Название проекта обновлено',
        'project' => $project
    ]);
}


}
