<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Subtask;
use App\Notifications\StagnantTaskReminder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Request;
use App\Models\Company;
class ProjectNotificationController extends Controller
{
//    public function remindStagnantTasks(Project $project)
//    {
//        // 1. Получаем все задачи и подзадачи проекта с прогрессом 0
//        // Загружаем сразу их участников, чтобы не делать лишних запросов в цикле (Eager Loading)
//        $stagnantTasks = Task::with(['executors', 'responsibles'])
//            ->where('project_id', $project->id)
//            ->where('progress', 0)
//            ->get();
//
//        $stagnantSubtasks = Subtask::with(['executors', 'responsibles'])
//            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
//            ->where('progress', 0)
//            ->get();
//
//        if ($stagnantTasks->isEmpty() && $stagnantSubtasks->isEmpty()) {
//            return response()->json(['message' => 'Нет задач с нулевым прогрессом.'], 404);
//        }
//
//        // 2. Собираем список ВСЕХ пользователей, которых нужно уведомить
//        $usersToNotify = collect();
//
//        // Добавляем менеджеров проекта (они должны знать обо всех зависших задачах)
//        $usersToNotify = $usersToNotify->concat($project->managers);
//
//        // Добавляем всех исполнителей и ответственных из зависших задач
//        foreach ($stagnantTasks as $task) {
//            $usersToNotify = $usersToNotify->concat($task->executors)->concat($task->responsibles);
//        }
//
//        // Добавляем всех исполнителей и ответственных из зависших подзадач
//        foreach ($stagnantSubtasks as $sub) {
//            $usersToNotify = $usersToNotify->concat($sub->executors)->concat($sub->responsibles);
//        }
//
//        // Оставляем только уникальных пользователей, чтобы не спамить одному человеку дважды
//        $usersToNotify = $usersToNotify->unique('id');
//
//        $sentCount = 0;
//
//        // 3. Рассылка уведомлений
//        foreach ($usersToNotify as $user) {
//            // Фильтруем задачи: либо пользователь менеджер (видит всё), либо он участник конкретной задачи
//            $isProjectManager = $project->managers->contains('id', $user->id);
//
//            $hisTasks = $isProjectManager ? $stagnantTasks : $stagnantTasks->filter(function($t) use ($user) {
//                return $t->executors->contains('id', $user->id) || $t->responsibles->contains('id', $user->id);
//            });
//
//            $hisSubtasks = $isProjectManager ? $stagnantSubtasks : $stagnantSubtasks->filter(function($s) use ($user) {
//                return $s->executors->contains('id', $user->id) || $s->responsibles->contains('id', $user->id);
//            });
//
//            // Отправляем, если есть о чем напомнить
//            if ($hisTasks->isNotEmpty() || $hisSubtasks->isNotEmpty()) {
//                $user->notify(new StagnantTaskReminder($project, $hisTasks, $hisSubtasks));
//                $sentCount++;
//            }
//        }
//
//        return response()->json([
//            'message' => "Уведомления отправлены {$sentCount} участникам."
//        ]);
//    }


// 1. Получаем список задач для выбора в модальном окне
    public function getStagnantItems(Project $project)
    {
        $tasks = Task::where('project_id', $project->id)
            ->where('progress', 0)
            ->get(['id', 'title']);

        $subtasks = Subtask::whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->where('progress', 0)
            ->with('task:id,title')
            ->get(['id', 'title', 'task_id']);

        return response()->json([
            'tasks' => $tasks,
            'subtasks' => $subtasks
        ]);
    }

// 2. Обновленный метод отправки
    public function remindStagnantTasks(Request $request, Project $project)
    {
        $validated = $request->validate([
            'task_ids' => 'array',
            'subtask_ids' => 'array',
        ]);

        // Выбираем только те задачи, которые прислал фронтенд
        $stagnantTasks = Task::with(['executors', 'responsibles'])
            ->whereIn('id', $validated['task_ids'] ?? [])
            ->where('project_id', $project->id)
            ->get();

        $stagnantSubtasks = Subtask::with(['executors', 'responsibles'])
            ->whereIn('id', $validated['subtask_ids'] ?? [])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->get();

        if ($stagnantTasks->isEmpty() && $stagnantSubtasks->isEmpty()) {
            return response()->json(['message' => 'Задачи не выбраны или не найдены.'], 422);
        }



        // (Код рассылки из вашего вопроса...)
        $usersToNotify = collect();
        $usersToNotify = $usersToNotify->concat($project->managers);
        foreach ($stagnantTasks as $task) { $usersToNotify = $usersToNotify->concat($task->executors)->concat($task->responsibles); }
        foreach ($stagnantSubtasks as $sub) { $usersToNotify = $usersToNotify->concat($sub->executors)->concat($sub->responsibles); }
        $usersToNotify = $usersToNotify->unique('id');

        $sentCount = 0;
        foreach ($usersToNotify as $user) {
            $isProjectManager = $project->managers->contains('id', $user->id);
            $hisTasks = $isProjectManager ? $stagnantTasks : $stagnantTasks->filter(fn($t) => $t->executors->contains('id', $user->id) || $t->responsibles->contains('id', $user->id));
            $hisSubtasks = $isProjectManager ? $stagnantSubtasks : $stagnantSubtasks->filter(fn($s) => $s->executors->contains('id', $user->id) || $s->responsibles->contains('id', $user->id));

            if ($hisTasks->isNotEmpty() || $hisSubtasks->isNotEmpty()) {
                $user->notify(new \App\Notifications\StagnantTaskReminder($project, $hisTasks, $hisSubtasks));
                $sentCount++;
            }
        }

        return response()->json(['message' => "Уведомления отправлены {$sentCount} участникам."]);
    }


//    public function remindCompanyStagnant(\App\Models\Company $company)
//    {
//        // 1. Получаем все проекты этой компании
//        $projects = $company->projects()->get();
//        $totalUsersNotified = 0;
//
//        foreach ($projects as $project) {
//            // Используем существующую логику поиска (которую мы писали ранее)
//            // Находим задачи и подзадачи с прогрессом 0
//            $stagnantTasks = Task::with(['executors', 'responsibles'])
//                ->where('project_id', $project->id)
//                ->where('progress', 0)
//                ->get();
//
//            $stagnantSubtasks = Subtask::with(['executors', 'responsibles'])
//                ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
//                ->where('progress', 0)
//                ->get();
//
//            if ($stagnantTasks->isEmpty() && $stagnantSubtasks->isEmpty()) {
//                continue; // В этом проекте всё ок, идем к следующему
//            }
//
//            // Собираем уникальных участников этого проекта
//            $projectUsers = collect()
//                ->concat($project->managers)
//                ->concat($stagnantTasks->flatMap->executors)
//                ->concat($stagnantTasks->flatMap->responsibles)
//                ->concat($stagnantSubtasks->flatMap->executors)
//                ->concat($stagnantSubtasks->flatMap->responsibles)
//                ->unique('id');
//
//            foreach ($projectUsers as $user) {
//                // Фильтруем задачи конкретно для этого пользователя в этом проекте
//                $isManager = $project->managers->contains('id', $user->id);
//
//                $hisTasks = $isManager ? $stagnantTasks : $stagnantTasks->filter(fn($t) =>
//                    $t->executors->contains('id', $user->id) || $t->responsibles->contains('id', $user->id)
//                );
//
//                $hisSubtasks = $isManager ? $stagnantSubtasks : $stagnantSubtasks->filter(fn($s) =>
//                    $s->executors->contains('id', $user->id) || $s->responsibles->contains('id', $user->id)
//                );
//
//                if ($hisTasks->isNotEmpty() || $hisSubtasks->isNotEmpty()) {
//                    $user->notify(new \App\Notifications\StagnantTaskReminder($project, $hisTasks, $hisSubtasks));
//                    $totalUsersNotified++;
//                }
//            }
//        }
//
//        return response()->json([
//            'message' => "Напоминания разосланы. Всего уведомлено участников: {$totalUsersNotified}"
//        ]);
//    }

    public function getCompanyStagnantItems(Company $company)
    {
        // Загружаем проекты, где есть задачи с прогрессом 0
        $data = $company->projects()->with([
            'tasks' => fn($q) => $q->where('progress', 0)->select('id', 'project_id', 'title'),
            'tasks.subtasks' => fn($q) => $q->where('progress', 0)->select('id', 'task_id', 'title')
        ])->get(['id', 'name']);

        // Фильтруем только те проекты, где реально есть хоть одна задача или подзадача
        $filtered = $data->filter(function($project) {
            $hasTasks = $project->tasks->isNotEmpty();
            $hasSubtasks = $project->tasks->some(fn($t) => $t->subtasks->isNotEmpty());
            return $hasTasks || $hasSubtasks;
        })->values();

        return response()->json($filtered);
    }

// 2. Рассылка по выбранным ID
    public function remindCompanyStagnant(Request $request, Company $company)
    {
        $validated = $request->validate([
            'task_ids' => 'array',
            'subtask_ids' => 'array',
        ]);

        $taskIds = $validated['task_ids'] ?? [];
        $subtaskIds = $validated['subtask_ids'] ?? [];

        // Получаем проекты компании, чтобы итерироваться по ним
        $projects = $company->projects()->get();
        $totalUsersNotified = 0;

        foreach ($projects as $project) {
            // Берем только те задачи из проекта, которые выбрал пользователь
            $stagnantTasks = Task::with(['executors', 'responsibles'])
                ->whereIn('id', $taskIds)
                ->where('project_id', $project->id)
                ->get();

            // Берем только те подзадачи из проекта, которые выбрал пользователь
            $stagnantSubtasks = Subtask::with(['executors', 'responsibles'])
                ->whereIn('id', $subtaskIds)
                ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
                ->get();

            if ($stagnantTasks->isEmpty() && $stagnantSubtasks->isEmpty()) {
                continue;
            }

            // Логика рассылки (собираем участников именно этого проекта)
            $projectUsers = collect()
                ->concat($project->managers)
                ->concat($stagnantTasks->flatMap->executors)
                ->concat($stagnantTasks->flatMap->responsibles)
                ->concat($stagnantSubtasks->flatMap->executors)
                ->concat($stagnantSubtasks->flatMap->responsibles)
                ->unique('id');

            foreach ($projectUsers as $user) {
                $isManager = $project->managers->contains('id', $user->id);
                $hisTasks = $isManager ? $stagnantTasks : $stagnantTasks->filter(fn($t) =>
                    $t->executors->contains('id', $user->id) || $t->responsibles->contains('id', $user->id)
                );
                $hisSubtasks = $isManager ? $stagnantSubtasks : $stagnantSubtasks->filter(fn($s) =>
                    $s->executors->contains('id', $user->id) || $s->responsibles->contains('id', $user->id)
                );

                if ($hisTasks->isNotEmpty() || $hisSubtasks->isNotEmpty()) {
                    $user->notify(new \App\Notifications\StagnantTaskReminder($project, $hisTasks, $hisSubtasks));
                    $totalUsersNotified++;
                }
            }
        }

        return response()->json(['message' => "Уведомления отправлены. Всего получателей: {$totalUsersNotified}"]);
    }

}
