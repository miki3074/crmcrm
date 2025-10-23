<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Models\Company;

class ProjectController extends Controller
{


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
        'watchers:id,name',
        'initiator:id,name',
        'subprojects.responsibles:id,name',
        'tasks' => function ($q) {
    $q->select('id', 'project_id', 'title', 'creator_id', 'start_date', 'due_date', 'priority', 'progress', 'completed') // ✅
      ->with([
          'creator:id,name',
          'executors:id,name',
          'responsibles:id,name',
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


// Добавить нового руководителя в проект
public function addManager(Request $request, Project $project)
{
    $this->authorize('updateman', $project);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    // Проверим, не добавлен ли уже этот руководитель
    if ($project->managers()->where('user_id', $validated['user_id'])->exists()) {
        return response()->json(['message' => 'Этот пользователь уже является руководителем проекта'], 422);
    }

    $project->managers()->attach($validated['user_id']);

    $user = \App\Models\User::find($validated['user_id']);
    $company = $project->company;

    if ($user && $user->telegram_chat_id) {
        \App\Services\TelegramService::sendMessage(
            $user->telegram_chat_id,
            "👋 Вы добавлены в качестве руководителя проекта: <b>{$project->name}</b>\nКомпания: {$company->name}"
        );
    }

    return response()->json([
        'message' => 'Руководитель успешно добавлен',
        'managers' => $project->managers()->get(['id', 'name']),
    ]);
}

// Заменить (изменить) руководителя
public function replaceManager(Request $request, Project $project)
{
    $this->authorize('updateman', $project);

    $validated = $request->validate([
        'old_manager_id' => 'required|exists:users,id',
        'new_manager_id' => 'required|exists:users,id|different:old_manager_id',
    ]);

    // Проверим, что старый руководитель действительно прикреплён
    if (!$project->managers()->where('user_id', $validated['old_manager_id'])->exists()) {
        return response()->json(['message' => 'Этот пользователь не является руководителем проекта'], 404);
    }

    // Удаляем старого и добавляем нового
    $project->managers()->detach($validated['old_manager_id']);
    $project->managers()->attach($validated['new_manager_id']);

    // Уведомляем нового руководителя
    $user = \App\Models\User::find($validated['new_manager_id']);
    $company = $project->company;

    if ($user && $user->telegram_chat_id) {
        \App\Services\TelegramService::sendMessage(
            $user->telegram_chat_id,
            "👔 Вы назначены руководителем проекта: <b>{$project->name}</b>\nКомпания: {$company->name}"
        );
    }

    return response()->json([
        'message' => 'Руководитель успешно изменён',
        'managers' => $project->managers()->get(['id', 'name']),
    ]);
}


public function destroy(Project $project)
{
    $this->authorize('deletepr', $project);

    // Удаляем все связанные данные
    foreach ($project->tasks as $task) {
        // удаляем файлы задач
        foreach ($task->files as $file) {
            if (\Storage::disk('public')->exists($file->file_path)) {
                \Storage::disk('public')->delete($file->file_path);
            }
            $file->delete();
        }

        // удаляем подзадачи
        foreach ($task->subtasks as $subtask) {
            $subtask->delete();
        }

        $task->delete();
    }

    // удаляем подпроекты (если есть)
    if (method_exists($project, 'subprojects')) {
        foreach ($project->subprojects as $sp) {
            $sp->delete();
        }
    }

    $project->delete();

    return response()->json(['message' => 'Проект и все связанные данные удалены.']);
}


// Добавить наблюдателя проекта
public function addWatcher(Request $request, Project $project)
{
    $this->authorize('updatewat', $project); // только владелец компании или менеджер

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $userId = $validated['user_id'];

    // 🚫 Нельзя добавить владельца компании как наблюдателя
    if ($userId == $project->company->user_id) {
        return response()->json(['message' => 'Владелец компании не может быть наблюдателем проекта'], 422);
    }

    // Проверим, не добавлен ли уже
    if ($project->watchers()->where('user_id', $userId)->exists()) {
        return response()->json(['message' => 'Этот пользователь уже является наблюдателем'], 422);
    }

    $project->watchers()->attach($userId);

    $user = User::find($userId);
    $company = $project->company;

    if ($user && $user->telegram_chat_id) {
        \App\Services\TelegramService::sendMessage(
            $user->telegram_chat_id,
            "👁 Вы добавлены как наблюдатель проекта: <b>{$project->name}</b>\nКомпания: {$company->name}"
        );
    }

    return response()->json([
        'message' => 'Наблюдатель успешно добавлен',
        'watchers' => $project->watchers()->select('users.id', 'users.name')->get(),
    ]);
}




// Удалить наблюдателя
public function removeWatcher(Request $request, Project $project)
{
    $this->authorize('updatewat', $project);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    $project->watchers()->detach($validated['user_id']);

    return response()->json([
        'message' => 'Наблюдатель удалён',
        'watchers' => $project->watchers()->select('users.id', 'users.name')->get(),
    ]);
}




}
