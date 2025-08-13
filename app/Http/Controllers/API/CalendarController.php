<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Company;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    // Список событий в интервале (FullCalendar шлёт start & end)
   public function index(Request $request)
{
    $user  = $request->user();
    $start = $request->query('start');
    $end   = $request->query('end');

    $q = \App\Models\Event::query()
        ->with(['attendees:id,name', 'company:id,name,user_id'])
        ->where(function($q) use ($user){
            $q->where('creator_id', $user->id)                                   // мои события
              ->orWhereHas('attendees', fn($qq)=>$qq->where('users.id',$user->id))// я участник
              ->orWhere(function($qq) use ($user){
                  $qq->where('visibility','company_all')
                     ->whereHas('company', function($c) use ($user){
                         $c->where('user_id', $user->id)              // я владелец
                           ->orWhere('user_id', $user->created_by ?? 0); // я сотрудник владельца
                     });
              });
        });

    if ($start && $end) {
        $q->where(function($qq) use ($start,$end){
            $qq->whereBetween('start_at', [$start,$end])
               ->orWhereBetween('end_at', [$start,$end])
               ->orWhere(function($x) use ($start,$end){
                   $x->where('start_at','<=',$start)->where('end_at','>=',$end);
               });
        });
    }

    return response()->json($q->get());
}

    // Создание
    public function store(Request $request)
{
    $user = $request->user();

    $data = $request->validate([
        'title'       => 'required|string|max:255',
        'description' => 'nullable|string',
        'visibility'  => 'required|in:personal,company_selected,company_all',
        'company_id'  => 'nullable|exists:companies,id',
        'start_at'    => 'required|date',
        'end_at'      => 'required|date|after_or_equal:start_at',
        'attendees'   => 'array',
        'attendees.*' => 'integer|exists:users,id',
    ]);

    if ($data['visibility'] !== 'personal') {
        abort_unless($data['company_id'], 422, 'company_id required');
        $isOwner = \App\Models\Company::where('id',$data['company_id'])
            ->where('user_id',$user->id)->exists();
        abort_unless($isOwner, 403);
    }

    $event = \App\Models\Event::create([
        ...$data,
        'creator_id' => $user->id,
    ]);

    if (($data['visibility'] ?? null) === 'company_selected' && !empty($data['attendees'])) {
        $event->attendees()->sync($data['attendees']);
    }

    return response()->json($event->load('attendees:id,name'), 201);
}

public function update(Request $request, \App\Models\Event $event)
{
    $user = $request->user();

    // Разрешения
    if ($event->visibility === 'personal') {
        abort_unless($event->creator_id === $user->id, 403);
    } else {
        abort_unless(optional($event->company)->user_id === $user->id, 403);
    }

    $data = $request->validate([
        'title'       => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'start_at'    => 'sometimes|required|date',
        'end_at'      => 'sometimes|required|date|after_or_equal:start_at',
        'attendees'   => 'array',
        'attendees.*' => 'integer|exists:users,id',
    ]);

    $event->update($data);

    if ($event->visibility === 'company_selected' && $request->has('attendees')) {
        $event->attendees()->sync($data['attendees'] ?? []);
    }

    return response()->json($event->fresh()->load('attendees:id,name'));
}

public function destroy(Request $request, \App\Models\Event $event)
{
    $user = $request->user();

    if ($event->visibility === 'personal') {
        abort_unless($event->creator_id === $user->id, 403);
    } else {
        abort_unless(optional($event->company)->user_id === $user->id, 403);
    }

    $event->delete();
    return response()->json(['ok' => true]);
}

}

