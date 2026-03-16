<?php

namespace App\Http\Controllers;

use App\Jobs\SendTelegramNotification;
use App\Models\User;
use App\Models\Company;
use App\Models\Message;
use App\Models\ChatGroup;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\TelegramService;
class ChatController extends Controller
{
    public function index($type = null, $targetId = null)
    {
        $user = Auth::user();

        // 1. Получаем компании с их участниками
        $ownedCompanies = $user->ownedCompanies()->with('users:id,name')->get();
        $memberCompanies = $user->companiesmess()->with('users:id,name')->get();

        $companies = $ownedCompanies->merge($memberCompanies)
            ->unique('id')
            ->values()
            ->map(function ($company) {
                // Собираем всех участников компании в один массив
                $participants = $company->users->map(function($u) {
                    return ['id' => $u->id, 'name' => $u->name];
                });

                // Добавляем владельца компании в список участников, если его там нет
                $owner = User::find($company->user_id);
                if ($owner && !$participants->contains('id', $owner->id)) {
                    $participants->push(['id' => $owner->id, 'name' => $owner->name]);
                }

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'members' => $participants->values()
                ];
            });

        $companyIds = $companies->pluck('id')->toArray();

        // 2. Коллеги для общего списка чатов (все уникальные)
        $colleagues = User::select('users.id', 'users.name')
            ->where('users.id', '!=', $user->id)
            ->where(function($query) use ($companyIds) {
                $query->whereHas('companiesmess', fn($q) => $q->whereIn('companies.id', $companyIds))
                    ->orWhereHas('ownedCompanies', fn($q) => $q->whereIn('companies.id', $companyIds));
            })
            ->distinct()
            ->get();

        // 3. Группы
        $groups = $user->chatGroups()->whereIn('company_id', $companyIds)->get();

        $messages = [];
        if ($type === 'user' && $targetId) {
            // Помечаем сообщения от этого пользователя как прочитанные
            Message::where('sender_id', $targetId)
                ->where('receiver_id', $user->id)
                ->whereNull('group_id')
                ->update(['is_read' => true]);

            // Загружаем переписку
            $messages = Message::whereNull('group_id')
                ->where(function($q) use ($user, $targetId) {
                    $q->where(function($i) use ($user, $targetId) {
                        $i->where('sender_id', $user->id)->where('receiver_id', $targetId);
                    })->orWhere(function($i) use ($user, $targetId) {
                        $i->where('sender_id', $targetId)->where('receiver_id', $user->id);
                    });
                })
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->get();
        } elseif ($type === 'group' && $targetId) {
            // Групповой чат
            $messages = Message::where('group_id', $targetId)
                ->with('sender')
                ->orderBy('created_at', 'asc')
                ->get();

        }
        $activeGroup = ChatGroup::with('users')->find($targetId);

        return Inertia::render('Chat/Index', [
            'activeGroup' => $activeGroup,
            'companies' => $companies,
            'groups' => $groups,
            'colleagues' => $colleagues,
            'messages' => $messages,
            'chatType' => $type,
            'targetId' => (int)$targetId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'type' => 'required|in:user,group',
            'target_id' => 'required'
        ]);

        $sender = Auth::user();
        $companyId = null;

        if ($request->type === 'user') {
            // Находим первую общую компанию для привязки сообщения
            $targetUser = User::find($request->target_id);
            $myCompanies = $sender->ownedCompanies()->pluck('companies.id')->merge($sender->companiesmess()->pluck('companies.id'));
            $targetCompanies = $targetUser->ownedCompanies()->pluck('companies.id')->merge($targetUser->companiesmess()->pluck('companies.id'));
            $companyId = $myCompanies->intersect($targetCompanies)->first();
        } else {
            $group = ChatGroup::find($request->target_id);
            $companyId = $group->company_id;
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'company_id' => $companyId,
            'message' => $request->message,
            'receiver_id' => $request->type === 'user' ? $request->target_id : null,
            'group_id' => $request->type === 'group' ? $request->target_id : null,
        ]);

        // 1. Уведомление для личного чата
        if ($request->type === 'user') {
            $receiver = User::find($request->target_id);
            if ($receiver && $receiver->telegram_chat_id) {
                $text = "💬 <b>Сообщение от {$sender->name}</b>\n\n{$request->message}";
                SendTelegramNotification::dispatch($receiver->telegram_chat_id, $text);
            }
        }
        // 2. Уведомление для группы
        else {
            $group = ChatGroup::with('users')->find($request->target_id);
            if ($group) {
                $text = "👥 <b>Группа: {$group->name}</b>\n<b>{$sender->name}:</b> {$request->message}";

                foreach ($group->users as $member) {
                    if ($member->id !== $sender->id && $member->telegram_chat_id) {
                        SendTelegramNotification::dispatch($member->telegram_chat_id, $text);
                    }
                }
            }
        }

        return back();
    }

    public function createGroup(Request $request)
    {
        /* ... ваша валидация и создание группы ... */
        $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required',
            'user_ids' => 'required|array'
        ]);

        $creator = Auth::user();
        $group = ChatGroup::create([
            'name' => $request->name,
            'company_id' => $request->company_id,
            'creator_id' => $creator->id
        ]);
        $group->users()->attach(array_merge($request->user_ids, [$creator->id]));

        // 3. Уведомление о добавлении в группу
        foreach ($request->user_ids as $userId) {
            $user = User::find($userId);
            if ($user && $user->telegram_chat_id) {
                $text = "🚀 <b>Вас добавили в группу чата</b>\nНазвание: <b>{$group->name}</b>\nКто добавил: {$creator->name}";
                SendTelegramNotification::dispatch($user->telegram_chat_id, $text);
            }
        }

        return back();
    }

    public function addMember(Request $request, ChatGroup $group)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        // Проверка: состоит ли пользователь уже в группе
        if (!$group->users()->where('users.id', $request->user_id)->exists()) {
            $group->users()->attach($request->user_id);

            // Опционально: уведомление в Телеграм
            $user = User::find($request->user_id);
            if ($user->telegram_chat_id) {
                $text = "📢 Вас добавили в группу <b>{$group->name}</b>";
                SendTelegramNotification::dispatch($user->telegram_chat_id, $text);
            }
        }

        return back();
    }

// Удаление участника
    public function removeMember(Request $request, ChatGroup $group)
    {
        $request->validate(['user_id' => 'required|exists:users,id']);

        // Нельзя удалить создателя (опционально)
        if ($group->creator_id == $request->user_id) {
            return back()->withErrors(['message' => 'Нельзя удалить создателя группы']);
        }

        $group->users()->detach($request->user_id);
        return back();
    }

}
