<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project; 
use App\Models\Subproject; 

class SubprojectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Project $project)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'responsible_id' => 'nullable|exists:users,id',
        ]);

        $subproject = $project->subprojects()->create($validated);

        return response()->json($subproject->load('responsible'), 201);
    }

    public function show(Subproject $subproject)
{
    $subproject->load(['project.company', 'responsible', 'tasks']);

    // Берём сотрудников компании проекта
    $employees = \App\Models\User::where('company_id', $subproject->project->company_id)
        ->get(['id','name']);

    return response()->json([
        'subproject' => $subproject,
        'employees'  => $employees,
    ]);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
