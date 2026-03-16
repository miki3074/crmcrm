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
    public function index($companyId = null, $type = null, $targetId = null)
    {
        $user = Auth::user();

        // 1. Все компании пользователя (владелец + участник)
        $companies = $user->ownedCompanies()->get()
            ->merge($user->companiesmess()->get())
            ->unique('id')->values();

        $groups = [];
        $colleagues = [];
        $messages = [];

        if ($companyId) {
            $selectedCompany = Company::findOrFail($companyId);

            // 2. Группы компании, в которых состоит юзер
            $groups = $user->chatGroups()->where('company_id', $companyId)->get();

            // 3. Коллеги (включая владельца компании)
            $participants = $selectedCompany->users()->get();
            $owner = User::find($selectedCompany->user_id);
            if ($owner) $participants->push($owner);
            $colleagues = $participants->where('id', '!=', $user->id)->unique('id')->values();

            // 4. Загрузка сообщений
            if ($type === 'user' && $targetId) {
                // Личный чат: помечаем прочитанным
                Message::where('company_id', $companyId)
                    ->where('sender_id', $targetId)
                    ->where('receiver_id', $user->id)
                    ->update(['is_read' => true]);

                $messages = Message::where('company_id', $companyId)
                    ->whereNull('group_id')
                    ->where(function($q) use ($user, $targetId) {
                        $q->where(function($i) use ($user, $targetId) {
                            $i->where('sender_id', $user->id)->where('receiver_id', $targetId);
                        })->orWhere(function($i) use ($user, $targetId) {
                            $i->where('sender_id', $targetId)->where('receiver_id', $user->id);
                        });
                    })->with('sender')->orderBy('created_at', 'asc')->get();

            } elseif ($type === 'group' && $targetId) {
                // Групповой чат
                $messages = Message::where('group_id', $targetId)
                    ->with('sender')
                    ->orderBy('created_at', 'asc')
                    ->get();
            }
        }

        return Inertia::render('Chat/Index', [
            'companies' => $companies,
            'groups' => $groups,
            'colleagues' => $colleagues,
            'messages' => $messages,
            'selectedCompanyId' => (int)$companyId,
            'chatType' => $type, // 'user' или 'group'
            'targetId' => (int)$targetId,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'message' => 'required|string',
            'type' => 'required|in:user,group',
            'target_id' => 'required'
        ]);

        $sender = Auth::user();
        $message = Message::create([
            /* ... ваши поля ... */
            'sender_id' => $sender->id,
            'company_id' => $request->company_id,
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
