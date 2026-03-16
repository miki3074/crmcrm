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

        // 1. Получаем ID всех компаний (явно указываем таблицу companies.id)
        $companyIds = $user->ownedCompanies()->pluck('companies.id')
            ->merge($user->companiesmess()->pluck('companies.id'))
            ->unique()
            ->toArray(); // Преобразуем в массив для надежности

        // 2. Получаем всех УНИКАЛЬНЫХ коллег из этих компаний
        $colleagues = User::select('users.id', 'users.name', 'users.email')
            ->where('users.id', '!=', $user->id)
            ->where(function($query) use ($companyIds) {
                // Коллеги, которые являются участниками в этих компаниях
                $query->whereHas('companiesmess', function($q) use ($companyIds) {
                    $q->whereIn('companies.id', $companyIds);
                })
                    // Или владельцы этих компаний
                    ->orWhereHas('ownedCompanies', function($q) use ($companyIds) {
                        $q->whereIn('companies.id', $companyIds);
                    });
            })
            ->distinct()
            ->get();

        // 3. Получаем все группы пользователя изо всех его компаний
        $groups = $user->chatGroups()
            ->whereIn('company_id', $companyIds)
            ->get();

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

        return Inertia::render('Chat/Index', [
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
}
