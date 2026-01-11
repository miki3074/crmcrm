<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
class ChatListController extends Controller
{
    public function index(Request $request)
    {
        return Chat::where('user_id', auth()->id())
            ->orWhere('session_id', session()->getId())
            ->orderByDesc('updated_at')
            ->get();
    }

    public function store()
    {
        $userId = auth()->id();
        $sessionId = session()->getId();

        // Считаем сколько чатов уже у пользователя
        $count = Chat::where('user_id', $userId)
            ->orWhere('session_id', $sessionId)
            ->count();

        $chat = Chat::create([
            'user_id' => $userId,
            'session_id' => auth()->check() ? null : $sessionId,
            'title' => 'Чат ' . ($count + 1), // Автоматически нумеруем
        ]);

        return $chat;
    }


    public function show(Chat $chat)
    {
        $this->authorizeChat($chat);

        return $chat->messages()->orderBy('id')->get();
    }

    private function authorizeChat(Chat $chat)
    {
        if (
            $chat->user_id !== auth()->id() &&
            $chat->session_id !== session()->getId()
        ) {
            abort(403);
        }
    }

    public function destroy(Chat $chat)
    {
        $this->authorizeChat($chat); // Проверяем, что пользователь имеет доступ

        $chat->messages()->delete(); // Удаляем все сообщения
        $chat->delete();             // Удаляем сам чат

        return response()->json(['message' => 'Чат удалён']);
    }



}
