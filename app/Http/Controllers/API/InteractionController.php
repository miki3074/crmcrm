<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Client;       // 👈 нужен
use App\Models\Interaction;  // 👈 нужен

// app/Http/Controllers/API/InteractionController.php
class InteractionController extends Controller
{
    public function store(Request $r, Client $client)
    {
        $v = $r->validate([
            'type' => 'required|in:call,meeting,email,comment',
            'content' => 'nullable|string',
            'interaction_date' => 'nullable|date',
        ]);

        return $client->interactions()->create([
            ...$v,
            'interaction_date' => $v['interaction_date'] ?? now(),
            'user_id' => $r->user()->id,
        ])->load('user:id,name');
    }
}

