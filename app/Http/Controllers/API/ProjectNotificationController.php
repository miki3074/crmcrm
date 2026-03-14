<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Subtask;
use App\Notifications\StagnantTaskReminder;
use Illuminate\Support\Facades\Notification;

class ProjectNotificationController extends Controller
{
    public function remindStagnantTasks(Project $project)
    {
        // 1. Получаем все задачи и подзадачи проекта с прогрессом 0
        // Загружаем сразу их участников, чтобы не делать лишних запросов в цикле (Eager Loading)
        $stagnantTasks = Task::with(['executors', 'responsibles'])
            ->where('project_id', $project->id)
            ->where('progress', 0)
            ->get();

        $stagnantSubtasks = Subtask::with(['executors', 'responsibles'])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->where('progress', 0)
            ->get();

        if ($stagnantTasks->isEmpty() && $stagnantSubtasks->isEmpty()) {
            return response()->json(['message' => 'Нет задач с нулевым прогрессом.'], 404);
        }

        // 2. Собираем список ВСЕХ пользователей, которых нужно уведомить
        $usersToNotify = collect();

        // Добавляем менеджеров проекта (они должны знать обо всех зависших задачах)
        $usersToNotify = $usersToNotify->concat($project->managers);

        // Добавляем всех исполнителей и ответственных из зависших задач
        foreach ($stagnantTasks as $task) {
            $usersToNotify = $usersToNotify->concat($task->executors)->concat($task->responsibles);
        }

        // Добавляем всех исполнителей и ответственных из зависших подзадач
        foreach ($stagnantSubtasks as $sub) {
            $usersToNotify = $usersToNotify->concat($sub->executors)->concat($sub->responsibles);
        }

        // Оставляем только уникальных пользователей, чтобы не спамить одному человеку дважды
        $usersToNotify = $usersToNotify->unique('id');

        $sentCount = 0;

        // 3. Рассылка уведомлений
        foreach ($usersToNotify as $user) {
            // Фильтруем задачи: либо пользователь менеджер (видит всё), либо он участник конкретной задачи
            $isProjectManager = $project->managers->contains('id', $user->id);

            $hisTasks = $isProjectManager ? $stagnantTasks : $stagnantTasks->filter(function($t) use ($user) {
                return $t->executors->contains('id', $user->id) || $t->responsibles->contains('id', $user->id);
            });

            $hisSubtasks = $isProjectManager ? $stagnantSubtasks : $stagnantSubtasks->filter(function($s) use ($user) {
                return $s->executors->contains('id', $user->id) || $s->responsibles->contains('id', $user->id);
            });

            // Отправляем, если есть о чем напомнить
            if ($hisTasks->isNotEmpty() || $hisSubtasks->isNotEmpty()) {
                $user->notify(new StagnantTaskReminder($project, $hisTasks, $hisSubtasks));
                $sentCount++;
            }
        }

        return response()->json([
            'message' => "Уведомления отправлены {$sentCount} участникам."
        ]);
    }

    public function remindCompanyStagnant(\App\Models\Company $company)
    {
        // 1. Получаем все проекты этой компании
        $projects = $company->projects()->get();
        $totalUsersNotified = 0;

        foreach ($projects as $project) {
            // Используем существующую логику поиска (которую мы писали ранее)
            // Находим задачи и подзадачи с прогрессом 0
            $stagnantTasks = Task::with(['executors', 'responsibles'])
                ->where('project_id', $project->id)
                ->where('progress', 0)
                ->get();

            $stagnantSubtasks = Subtask::with(['executors', 'responsibles'])
                ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
                ->where('progress', 0)
                ->get();

            if ($stagnantTasks->isEmpty() && $stagnantSubtasks->isEmpty()) {
                continue; // В этом проекте всё ок, идем к следующему
            }

            // Собираем уникальных участников этого проекта
            $projectUsers = collect()
                ->concat($project->managers)
                ->concat($stagnantTasks->flatMap->executors)
                ->concat($stagnantTasks->flatMap->responsibles)
                ->concat($stagnantSubtasks->flatMap->executors)
                ->concat($stagnantSubtasks->flatMap->responsibles)
                ->unique('id');

            foreach ($projectUsers as $user) {
                // Фильтруем задачи конкретно для этого пользователя в этом проекте
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

        return response()->json([
            'message' => "Напоминания разосланы. Всего уведомлено участников: {$totalUsersNotified}"
        ]);
    }


}
