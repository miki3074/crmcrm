<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;

class CompanyMapController extends Controller
{
    public function show(Company $company)
    {
        $this->authorize('view', $company);

        // Загружаем всю иерархию и связи
        $company->load([
            'projects.tasks.subtasks.children',
            'projects.executors',
            'projects.managers',
            'projects.watchers',
            'projects.tasks.executor',
            'projects.tasks.responsible',
            'projects.tasks.watcherstask',
            // belongsToMany связи для подзадач:
            'projects.tasks.subtasks.executors',
            'projects.tasks.subtasks.responsibles',
            'projects.tasks.subtasks.children.executors',
            'projects.tasks.subtasks.children.responsibles',
        ]);

        return response()->json([
            'id' => $company->id,
            'name' => $company->name,
            'projects' => $company->projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date,
                    'end_date' => $project->end_date,
                    'budget' => $project->budget,
                    'description' => $project->description,
                    'executors' => $project->executors->pluck('name'),
                    'managers' => $project->managers->pluck('name'),
                    'watchers' => $project->watchers->pluck('name'),

                    // === Задачи ===
                    'tasks' => $project->tasks->map(function ($task) {
                        return [
                            'id' => $task->id,
                            'title' => $task->title,
                            'description' => $task->description,
                            'progress' => $task->progress,
                            'responsible' => $task->responsible ? [
                                'id' => $task->responsible->id,
                                'name' => $task->responsible->name,
                            ] : null,
                            'executor' => $task->executor ? [
                                'id' => $task->executor->id,
                                'name' => $task->executor->name,
                            ] : null,
                            'watchers' => $task->watcherstask?->pluck('name') ?? [],
                            'subtask_count' => $task->subtasks->count(),

                            // === Подзадачи ===
                            'subtasks' => $task->subtasks->map(function ($sub) {
                                return [
                                    'id' => $sub->id,
                                    'title' => $sub->title,
                                    'progress' => $sub->progress,

                                    // ✅ Исполнители и ответственные (многие)
                                    'executors' => $sub->executors->map(fn($u) => [
                                        'id' => $u->id,
                                        'name' => $u->name,
                                    ]),
                                    'responsibles' => $sub->responsibles->map(fn($u) => [
                                        'id' => $u->id,
                                        'name' => $u->name,
                                    ]),

                                    // === Дочерние подзадачи ===
                                    'children' => $sub->children->map(function ($child) {
                                        return [
                                            'id' => $child->id,
                                            'title' => $child->title,
                                            'progress' => $child->progress,

                                            // ✅ То же самое для детей
                                            'executors' => $child->executors->map(fn($u) => [
                                                'id' => $u->id,
                                                'name' => $u->name,
                                            ]),
                                            'responsibles' => $child->responsibles->map(fn($u) => [
                                                'id' => $u->id,
                                                'name' => $u->name,
                                            ]),
                                        ];
                                    }),
                                ];
                            }),
                        ];
                    }),
                ];
            }),
        ]);
    }
}
