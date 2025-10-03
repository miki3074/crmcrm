<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

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

// ================== Проверка прав ==================
 protected function canManageCompany($userId, $companyId): bool
{
    $company = \App\Models\Company::find($companyId);

    if (!$company) return false;

    // 1. Владелец компании
    if ($company->user_id == $userId) {
        return true;
    }

    // 2. Менеджеры в pivot
    return $company->users()
        ->wherePivot('role', 'manager')
        ->where('users.id', $userId)
        ->exists();
}



    // Создание
//     public function store(Request $request)
// {
//     $user = $request->user();

//     $data = $request->validate([
//         'title'       => 'required|string|max:255',
//         'description' => 'nullable|string',
//         'visibility'  => 'required|in:personal,company_selected,company_all',
//         'company_id'  => 'nullable|exists:companies,id',
//         'start_at'    => 'required|date',
//         'end_at'      => 'required|date|after_or_equal:start_at',
//         'attendees'   => 'array',
//         'attendees.*' => 'integer|exists:users,id',
//     ]);

//     if ($data['visibility'] !== 'personal') {
//         abort_unless($data['company_id'], 422, 'company_id required');
//         $isOwner = \App\Models\Company::where('id',$data['company_id'])
//             ->where('user_id',$user->id)->exists();
//         abort_unless($isOwner, 403);
//     }

//     $event = \App\Models\Event::create([
//         ...$data,
//         'creator_id' => $user->id,
//     ]);

//     if (($data['visibility'] ?? null) === 'company_selected' && !empty($data['attendees'])) {
//         $event->attendees()->sync($data['attendees']);
//     }

//     $recipients = [];

//     if ($data['visibility'] === 'personal') {
//         // только сам создатель
//         $recipients = [$user->id];
//     } elseif ($data['visibility'] === 'company_all') {
//         // все сотрудники компании
//         $recipients = \App\Models\User::where('company_id', $data['company_id'])->pluck('id')->toArray();
//     } elseif ($data['visibility'] === 'company_selected') {
//         // только выбранные участники
//         $recipients = $data['attendees'] ?? [];
//     }

//     $recipients = array_unique($recipients);

//     foreach ($recipients as $uid) {
//         $u = \App\Models\User::find($uid);
//         if ($u && $u->telegram_chat_id) {
//             \App\Services\TelegramService::sendMessage(
//                 $u->telegram_chat_id,
//                 "📅 Новое событие: <b>{$event->title}</b>\n".
//                 ($event->description ? "Описание: {$event->description}\n" : "").
//                 "Начало: {$event->start_at}\n".
//                 "Окончание: {$event->end_at}"
//             );
//         }
//     }

//     return response()->json($event->load('attendees:id,name'), 201);
// }

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
        abort_unless($data['company_id'], 422, 'Не указана компания');
        abort_unless($this->canManageCompany($user->id, $data['company_id']), 403, 'Нет прав управлять этой компанией');
    }

    $event = Event::create([
        ...$data,
        'creator_id' => $user->id,
    ]);

    // ================== Кому слать уведомления ==================
    $recipients = [];

    if ($data['visibility'] === 'personal') {
        $recipients = [$user->id];

    } elseif ($data['visibility'] === 'company_all') {
        $recipients = Company::find($data['company_id'])
            ->users()
            ->pluck('users.id')
            ->toArray();

    } elseif ($data['visibility'] === 'company_selected') {
        $company = Company::find($data['company_id']);
        $ownerId = $company->user_id;

        // объединяем участников и владельца
        $attendees = array_unique(array_merge(
            $data['attendees'] ?? [],
            [$ownerId]
        ));

        $event->attendees()->sync($attendees);

        $recipients = $company->users()
            ->whereIn('users.id', $attendees)
            ->pluck('users.id')
            ->toArray();

        $recipients[] = $ownerId;
    }

    $recipients = array_unique($recipients);

    foreach ($recipients as $uid) {
        $u = \App\Models\User::find($uid);
        if ($u && $u->telegram_chat_id) {
            \App\Services\TelegramService::sendMessage(
                $u->telegram_chat_id,
                "📅 Новое событие: <b>{$event->title}</b>\n".
                ($event->description ? "Описание: {$event->description}\n" : "").
                "Начало: {$event->start_at}\n".
                "Окончание: {$event->end_at}"
            );
        }
    }

    return response()->json($event->load('attendees:id,name'), 201);
}


// public function update(Request $request, \App\Models\Event $event)
// {
//     $user = $request->user();

//     // Разрешения
//     if ($event->visibility === 'personal') {
//         abort_unless($event->creator_id === $user->id, 403);
//     } else {
//         abort_unless(optional($event->company)->user_id === $user->id, 403);
//     }

//     $data = $request->validate([
//         'title'       => 'sometimes|required|string|max:255',
//         'description' => 'nullable|string',
//         'start_at'    => 'sometimes|required|date',
//         'end_at'      => 'sometimes|required|date|after_or_equal:start_at',
//         'attendees'   => 'array',
//         'attendees.*' => 'integer|exists:users,id',
//     ]);

//     $event->update($data);

//     if ($event->visibility === 'company_selected' && $request->has('attendees')) {
//         $event->attendees()->sync($data['attendees'] ?? []);
//     }

//     $recipients = [];

//     if ($event->visibility === 'personal') {
//         $recipients = [$event->creator_id];
//     } elseif ($event->visibility === 'company_all') {
//         $recipients = \App\Models\User::where('company_id', $event->company_id)->pluck('id')->toArray();
//     } elseif ($event->visibility === 'company_selected') {
//         $recipients = $event->attendees()->pluck('users.id')->toArray();
//     }

//     $recipients = array_unique($recipients);

//     foreach ($recipients as $uid) {
//         $u = \App\Models\User::find($uid);
//         if ($u && $u->telegram_chat_id) {
//             \App\Services\TelegramService::sendMessage(
//                 $u->telegram_chat_id,
//                 "✏️ Событие обновлено: <b>{$event->title}</b>\n".
//                 ($event->description ? "Описание: {$event->description}\n" : "").
//                 "Начало: {$event->start_at}\n".
//                 "Окончание: {$event->end_at}"
//             );
//         }
//     }

//     return response()->json($event->fresh()->load('attendees:id,name'));
// }

// public function destroy(Request $request, \App\Models\Event $event)
// {
//     $user = $request->user();

//     if ($event->visibility === 'personal') {
//         abort_unless($event->creator_id === $user->id, 403);
//     } else {
//         abort_unless(optional($event->company)->user_id === $user->id, 403);
//     }

//         $recipients = [];

//     if ($event->visibility === 'personal') {
//         $recipients = [$event->creator_id];
//     } elseif ($event->visibility === 'company_all') {
//         $recipients = \App\Models\User::where('company_id', $event->company_id)->pluck('id')->toArray();
//     } elseif ($event->visibility === 'company_selected') {
//         $recipients = $event->attendees()->pluck('users.id')->toArray();
//     }

//     $recipients = array_unique($recipients);

//     foreach ($recipients as $uid) {
//         $u = \App\Models\User::find($uid);
//         if ($u && $u->telegram_chat_id) {
//             \App\Services\TelegramService::sendMessage(
//                 $u->telegram_chat_id,
//                 "🗑 Событие отменено: <b>{$event->title}</b>\n".
//                 ($event->description ? "Описание: {$event->description}\n" : "").
//                 "Период: {$event->start_at} → {$event->end_at}"
//             );
//         }
//     }


//     $event->delete();
//     return response()->json(['ok' => true]);
// }



private function canEditEvent(User $user, Event $event): bool
{
    if ($event->visibility === 'personal') {
        return $event->creator_id === $user->id;
    }

    if ($event->company_id) {
        // владелец или менеджер
        return $this->canManageCompany($user->id, $event->company_id);
    }

    return false;
}



// ================== Обновление ==================
    public function update(Request $request, Event $event)
    {
        $user = $request->user();

        abort_unless($this->canEditEvent($user, $event), 403);

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

        $recipients = [];

        if ($event->visibility === 'personal') {
            $recipients = [$event->creator_id];

        } elseif ($event->visibility === 'company_all') {
            $recipients = Company::find($event->company_id)
                ->users()
                ->pluck('users.id')
                ->toArray();

        } elseif ($event->visibility === 'company_selected') {
            $recipients = Company::find($event->company_id)
                ->users()
                ->whereIn('users.id', $event->attendees()->pluck('users.id')->toArray())
                ->pluck('users.id')
                ->toArray();
        }

        $recipients = array_unique($recipients);

        foreach ($recipients as $uid) {
            $u = \App\Models\User::find($uid);
            if ($u && $u->telegram_chat_id) {
                \App\Services\TelegramService::sendMessage(
                    $u->telegram_chat_id,
                    "✏️ Событие обновлено: <b>{$event->title}</b>\n".
                    ($event->description ? "Описание: {$event->description}\n" : "").
                    "Начало: {$event->start_at}\n".
                    "Окончание: {$event->end_at}"
                );
            }
        }

        return response()->json($event->fresh()->load('attendees:id,name'));
    }

    // ================== Удаление ==================
    public function destroy(Request $request, Event $event)
    {
        $user = $request->user();

        abort_unless($this->canEditEvent($user, $event), 403);

        $recipients = [];

        if ($event->visibility === 'personal') {
            $recipients = [$event->creator_id];

        } elseif ($event->visibility === 'company_all') {
            $recipients = Company::find($event->company_id)
                ->users()
                ->pluck('users.id')
                ->toArray();

        } elseif ($event->visibility === 'company_selected') {
            $recipients = Company::find($event->company_id)
                ->users()
                ->whereIn('users.id', $event->attendees()->pluck('users.id')->toArray())
                ->pluck('users.id')
                ->toArray();
        }

        $recipients = array_unique($recipients);

        foreach ($recipients as $uid) {
            $u = \App\Models\User::find($uid);
            if ($u && $u->telegram_chat_id) {
                \App\Services\TelegramService::sendMessage(
                    $u->telegram_chat_id,
                    "🗑 Событие отменено: <b>{$event->title}</b>\n".
                    ($event->description ? "Описание: {$event->description}\n" : "").
                    "Период: {$event->start_at} → {$event->end_at}"
                );
            }
        }

        $event->delete();
        return response()->json(['ok' => true]);
    }
}



