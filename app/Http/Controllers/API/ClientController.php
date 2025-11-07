<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Client;
use App\Models\Interaction;
use App\Models\Deal;

use App\Models\Project;
use App\Models\User;
use App\Models\Company;
use DB; 

class ClientController extends Controller
{
     public function index()
{
    $userId = auth()->id();

    return \App\Models\Client::withCount(['deals','interactions'])
        ->where(function ($q) use ($userId) {
            $q->where('created_by', $userId)
              ->orWhere('responsible_id', $userId);
        })
        ->latest()
        ->get();
}

public function store(Request $r)
{
    $v = $r->validate([
        'type'           => 'required|in:jur,fiz',
        'name'           => 'required|string|max:255',
        'organization_name' => 'nullable|string|max:255',
        'email'          => 'nullable|email',
        'phone'          => 'nullable|string|max:50',
        'city'           => 'nullable|string|max:255',
        'address'        => 'nullable|string|max:255',
        'project_id'     => 'nullable|exists:projects,id',
        'responsible_id' => 'nullable|exists:users,id',
        'notes'          => 'nullable|string',
    ]);

    $v['created_by'] = auth()->id();

    if ($r->filled('project_id') && $r->filled('responsible_id')) {
        $project = \App\Models\Project::select('id','company_id')->findOrFail($r->project_id);

        $inCompany = \DB::table('company_user')
            ->where('company_id', $project->company_id)
            ->where('user_id', $r->responsible_id)
            ->exists();

        abort_if(!$inCompany, 422, 'Ответственный должен быть сотрудником компании выбранного проекта.');
    }

    $client = \App\Models\Client::create($v);

    return $client->load('creator:id,name');
}


public function show(Client $client)
{
    $this->authorize('view', $client);

    return $client->load([
        'company:id,name',
        'project:id,name,company_id',
        'project.company:id,name',
        'responsible:id,name',
        'creator:id,name',
        'deals:id,client_id,title,amount,status,created_at',
        'interactions' => fn($q) => $q->with('user:id,name'),
    ]);
}

public function update(Request $r, Client $client)
{
    $this->authorize('update', $client);

    $v = $r->validate([
        'name' => 'required|string|max:255',
        'organization_name' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:50',
        'email' => 'nullable|email',
        'notes' => 'nullable|string',
    ]);

    $client->update($v);

    return $client->fresh()->load('project.company','responsible','creator');
}

public function destroy(Client $client)
{
    $this->authorize('delete', $client);

    $client->delete();
    return response()->json(['message' => 'Клиент успешно удалён.']);
}



}

