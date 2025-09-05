<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Client;
use App\Models\Interaction;
use App\Models\Deal;

class ClientController extends Controller
{
     public function index()
    {
        return Client::withCount(['deals','interactions'])->latest()->get();
    }

    public function store(Request $r)
    {
        $v = $r->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);
        return Client::create($v);
    }

    public function show(Client $client)
    {
        return $client->load([
            'company:id,name',
            'deals:id,client_id,title,amount,status,created_at',
            'interactions' => fn($q) => $q->with('user:id,name'),
        ]);
    }

    public function update(Request $r, Client $client)
    {
        $v = $r->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email',
            'notes' => 'nullable|string',
        ]);
        $client->update($v);
        return $client->fresh();
    }
}

