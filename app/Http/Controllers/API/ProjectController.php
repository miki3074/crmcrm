<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Models\Company;

use App\Models\TaskFile;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;
use Carbon\CarbonPeriod;


class ProjectController extends Controller
{




public function groupedByCompany()
{
    $user = auth()->user();

    $companies = \App\Models\Company::where('user_id', $user->id)
        ->select('id', 'name')
        ->get();

    $companies->load(['projects:id,name,company_id']);

    return response()->json($companies);
}




public function index()
{
    // Возвращаем проекты, к которым у пользователя есть доступ
    $user = auth()->user();

    // Если пользователь — владелец компании
    $companies = \App\Models\Company::where('user_id', $user->id)->pluck('id');

    // Все проекты этих компаний
    $projects = \App\Models\Project::whereIn('company_id', $companies)
        ->with(['company:id,name'])
        ->select('id', 'name', 'company_id')
        ->orderByDesc('created_at')
        ->get();

    return response()->json($projects);
}





public function store(Request $request)
{
    $messages = [
        'name.required' => 'Введите название проекта.',
        'name.string'   => 'Название проекта должно быть строкой.',
        'name.max'      => 'Название проекта не может быть длиннее 255 символов.',

        'manager_ids.required' => 'Выберите хотя бы одного менеджера.',
        'manager_ids.array'    => 'Поле менеджеров должно быть списком.',
        'manager_ids.min'      => 'Выберите хотя бы одного менеджера.',
        'manager_ids.*.exists' => 'Один из выбранных менеджеров не найден.',
        'start_date.required'  => 'Укажите дату начала проекта.',
        'start_date.date'      => 'Дата начала должна быть корректной.',
        'duration_days.required' => 'Укажите длительность проекта.',
        'duration_days.integer'  => 'Длительность должна быть числом.',
        'duration_days.min'      => 'Минимальная длительность — 1 день.',
        'company_id.required'  => 'Компания обязательна для выбора.',
        'company_id.exists'    => 'Указанная компания не найдена.',
    ];

    $request->validate([
        'name' => 'required|string|max:255',
        'manager_ids' => 'required|array|min:1',
        'manager_ids.*' => 'exists:users,id',
        'start_date' => 'required|date',
        'duration_days' => 'required|integer|min:1',
        'company_id' => 'required|exists:companies,id',
    ], $messages);

    $company = Company::findOrFail($request->company_id);
    $user = $request->user();

    $isOwner = $company->user_id === $user->id;

    $isManager = $company->users()
        ->wherePivot('role', 'manager')
        ->where('users.id', $user->id)
        ->exists();

    if (!$isOwner && !$isManager) {
        return response()->json([
            'message' => 'Только владелец компании или менеджер компании могут создавать проекты.',
        ], 403);
    }

  $project = Project::create([
    'name'           => $request->name,
    'start_date'     => $request->start_date,
    'duration_days'  => $request->duration_days,
    'company_id'     => $request->company_id,
    'initiator_id'   => $user->id,
]);

// Добавляем выбранных руководителей
// $project->managers()->syncWithoutDetaching($request->manager_ids);

// 📌 Если создатель — менеджер компании, тоже добавляем его
// if ($isManager && !$isOwner) {
//     $project->managers()->syncWithoutDetaching([$user->id]);
// }

    // ---------------

    // Добавляем выбранных руководителей
    $project->managers()->attach($request->manager_ids);

    foreach ($request->manager_ids as $userId) {
        $user = User::find($userId);
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
        $user = auth()->user();

        // 1. Загружаем сам ПРОЕКТ и глобальные роли
        $project = Project::with([
            'managers:id,name',
            'company:id,name,user_id',
            'watchers:id,name',
            'executors:id,name',
            'initiator:id,name',
            'subprojects.responsibles:id,name',
            'clients' => fn($q) => $q->with('responsible:id,name'),
        ])
            ->select('id', 'company_id', 'initiator_id', 'name', 'start_date', 'duration_days', 'budget', 'description')
            ->findOrFail($id);

        $this->authorize('view', $project);

        // 2. Определяем, есть ли у пользователя ПОЛНЫЙ доступ
        $hasFullAccess = (
            $project->company->user_id === $user->id ||
            $project->managers->contains('id', $user->id) ||
            $project->executors->contains('id', $user->id)
        );

        // 3. Догружаем (load) задачи с условием
        $project->load(['tasks' => function ($query) use ($user, $hasFullAccess) {

            // Выбираем поля и грузим связи для задач
            $query->select('id','project_id','title','creator_id','start_date','due_date','priority','progress','completed')
                ->with([
                    'creator:id,name',
                    'executors:id,name',
                    'responsibles:id,name',
                    'files:id,task_id,file_path',
                    // 🔥 ВАЖНО: Загружаем подзадачи с их исполнителями и ответственными
                    'subtasks' => function($q) {
                        $q->with(['executors:id,name', 'responsibles:id,name']);
                    }
                ]);

            // 🔥 Фильтрация задач
            if (!$hasFullAccess) {
                $query->where(function ($q) use ($user) {
                    $q->where('creator_id', $user->id) // Создатель
                    ->orWhereHas('executors', fn($sq) => $sq->where('users.id', $user->id)) // Исполнитель
                    ->orWhereHas('responsibles', fn($sq) => $sq->where('users.id', $user->id)) // Ответственный

                    ->orWhereHas('project.watchers', fn($sq) => $sq->where('users.id', $user->id))

                    // 🔥 НОВОЕ: Исполнитель подзадачи
                    ->orWhereHas('subtasks.executors', function($sq) use ($user) {
                        $sq->where('users.id', $user->id);
                    })

                        // 🔥 НОВОЕ: Ответственный подзадачи
                        ->orWhereHas('subtasks.responsibles', function($sq) use ($user) {
                            $sq->where('users.id', $user->id);
                        });
                });
            }
        }]);

        // 4. Добавляем флаг для фронтенда (опционально)
        $project->tasks->each(function($task) use ($user) {
            $task->user_in_subtasks = $task->subtasks->contains(function($subtask) use ($user) {
                return $subtask->executors->contains('id', $user->id) ||
                    $subtask->responsibles->contains('id', $user->id);
            });
        });

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

     $messages = [
        'name.required' => 'Введите название.',

    ];

    $validated = $request->validate([
        'name' => 'required|string|max:255',
    ], $messages);

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
        // Здесь тоже лучше уточнить, чтобы избежать будущих ошибок, хотя exists() обычно умный
        if ($project->managers()->where('users.id', $validated['user_id'])->exists()) {
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
            // 👇 Явно указываем таблицу users
            'managers' => $project->managers()->get(['users.id', 'users.name']),
        ]);
    }

// Заменить (изменить) руководителя
public function replaceManager(Request $request, Project $project)
{
    $this->authorize('updateman', $project);

      $messages = [
        'old_manager_id.required' => 'Укажите текущего руководителя.',
        'old_manager_id.exists' => 'Текущий руководитель не найден.',

        'new_manager_id.required' => 'Укажите нового руководителя.',
        'new_manager_id.exists' => 'Новый руководитель не найден.',
        'new_manager_id.different' => 'Новый руководитель должен отличаться от старого.',
    ];

    $validated = $request->validate([
        'old_manager_id' => 'required|exists:users,id',
        'new_manager_id' => 'required|exists:users,id|different:old_manager_id',
    ], $messages);

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


public function download($id)
    {
        $file = TaskFile::with('task.project.company')->findOrFail($id);

        // Проверка прав
        $this->authorize('view', $file->task);

        $path = $file->file_path;

        if (!Storage::disk('public')->exists($path)) {
            return response()->json(['message' => 'Файл не найден.'], Response::HTTP_NOT_FOUND);
        }

        // Отправляем с оригинальным именем, если есть
        return Storage::disk('public')->download($path, $file->original_name ?? basename($path));
    }

    // ✅ Добавить исполнителя в проект
public function addExecutor(Request $request, Project $project)
{
    $this->authorize('update', $project); // только менеджер/владелец

    $validated = $request->validate([
        'user_ids' => 'required|array|min:1',
        'user_ids.*' => 'exists:users,id',
    ]);

    // Добавляем без удаления старых
    $project->executors()->syncWithoutDetaching($validated['user_ids']);

    // Telegram уведомления
    foreach ($validated['user_ids'] as $id) {
        $user = \App\Models\User::find($id);
        if ($user && $user->telegram_chat_id) {
            \App\Services\TelegramService::sendMessage(
                $user->telegram_chat_id,
                "👷‍♂️ Вы добавлены как исполнитель проекта: <b>{$project->name}</b>\nКомпания: {$project->company->name}"
            );
        }
    }

    return response()->json([
        'message' => 'Исполнители успешно добавлены',
        'executors' => $project->executors()->select('users.id', 'users.name')->get(),
    ]);
}

// ✅ Удалить исполнителя
public function removeExecutor(Request $request, Project $project)
{
    $this->authorize('update', $project);

    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
    ]);

    // Проверяем, что после удаления останется хотя бы один
    if ($project->executors()->count() <= 1) {
        return response()->json([
            'message' => 'Нельзя удалить всех исполнителей из проекта. Должен остаться хотя бы один.'
        ], 422);
    }

    $project->executors()->detach($validated['user_id']);

    return response()->json([
        'message' => 'Исполнитель удалён',
        'executors' => $project->executors()->select('users.id', 'users.name')->get(),
    ]);
}


public function tasks(Project $project)
{
    $this->authorize('view', $project);

    $project->load(['tasks.executors:id,name']);

    return response()->json(
        $project->tasks->map(function ($task) {
            $end = \Carbon\Carbon::parse($task->start_date)
                ->addDays(\Carbon\Carbon::parse($task->start_date)->diffInDays($task->due_date))
                ->format('Y-m-d');

            return [
                'id' => $task->id,
                'title' => $task->title,
                'start_date' => $task->start_date,
                'due_date' => $task->due_date,
                'executor' => $task->executor?->name,
                'priority' => $task->priority,
            ];
        })
    );
}


public function taskStats(Project $project)
{
    $this->authorize('view', $project);

    $project->load('tasks.subtasks');

    $stats = $project->tasks->map(function ($task) {
        $subtasks = $task->subtasks;

        $total = $subtasks->count();
        $overdueSubtasks = $subtasks->where('due_date', '<', now())
                                    ->where('status', '!=', 'completed')
                                    ->count();

        // Проверяем саму задачу
        $isOverdue = !$task->completed && $task->due_date < now();

        return [
            'id' => $task->id,
            'title' => $task->title,
            'progress' => $task->progress ?? 0,
            'subtasks_total' => $total,
            'subtasks_overdue' => $overdueSubtasks,
            'is_overdue' => $isOverdue, // 👈 добавили
            'due_date' => $task->due_date,
        ];
    });

    return response()->json($stats);
}



    public function remove(Request $request, Project $project)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role'    => 'required|in:managers,executors,watchers', // Обратите внимание на множественное число, если так называются отношения
        ]);

        $userId = $request->user_id;
        $role = $request->role;

        // Проверка прав (опционально, если есть Policy)
        // $this->authorize('update', $project);

        if ($role === 'managers') {
            if ($project->managers()->count() <= 1) {
                return response()->json(['message' => 'Нельзя удалить последнего руководителя'], 422);
            }
            $project->managers()->detach($userId);
        }
        elseif ($role === 'executors') {

            $project->executors()->detach($userId);
        }
        elseif ($role === 'watchers') {
            $project->watchers()->detach($userId);
        }

        return response()->json(['success' => true, 'message' => 'Участник исключен']);
    }




}
