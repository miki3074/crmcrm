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
                    'manager_id' => 'required|exists:users,id',
                    'start_date' => 'required|date',
                    'duration_days' => 'required|integer|min:1',
                    'company_id' => 'required|exists:companies,id',
                ]);

                $project = Project::create([
                    'name' => $request->name,
                    'manager_id' => $request->manager_id,
                    'start_date' => $request->start_date,
                    'duration_days' => $request->duration_days,
                    'company_id' => $request->company_id,
                    'initiator_id' => auth()->id(),
                ]);

                return response()->json($project, 201);
            }

        public function show($id)
{
    $project = Project::with([
        'manager:id,name',
        'company:id,name,user_id', 
        'initiator:id,name',
        'tasks' => function ($q) {
            $q->with([
                'creator:id,name',
                'executor:id,name',
                'responsible:id,name',
                'files:id,task_id,file_path',
            ]);
        }
    ])->findOrFail($id);

    $this->authorize('view', $project); // если используется политика

    return response()->json($project);
}

            public function employees(Project $project)
{
    $company = $project->company;

    // Все сотрудники компании (созданные этим пользователем)
    $employees = \App\Models\User::where('created_by', $company->user_id)
        ->orWhere('id', $company->user_id) // Добавляем владельца компании
        ->get(['id', 'name']);

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


}
