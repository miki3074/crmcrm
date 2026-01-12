<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;

class ChatListController extends Controller
{

    private $accessPassword = '1123581321';


    private function checkAccess()
    {
        if (!session('chat_access_granted')) {
            abort(403, 'Password required');
        }
    }


    public function auth(Request $request)
    {
        if ($request->password === $this->accessPassword) {

            session(['chat_access_granted' => true]);
            return response()->json(['message' => 'Access granted']);
        }

        return response()->json(['message' => 'Invalid password'], 401);
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        return Chat::where('user_id', auth()->id())
            ->orWhere('session_id', session()->getId())
            ->orderByDesc('updated_at')
            ->get();
    }

    public function store()
    {
        $this->checkAccess();

        $userId = auth()->id();
        $sessionId = session()->getId();

        $count = Chat::where('user_id', $userId)
            ->orWhere('session_id', $sessionId)
            ->count();

        $chat = Chat::create([
            'user_id' => $userId,
            'session_id' => auth()->check() ? null : $sessionId,
            'title' => 'Чат ' . ($count + 1),
        ]);

        return $chat;
    }

    public function show(Chat $chat)
    {
        $this->checkAccess();
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
        $this->checkAccess();
        $this->authorizeChat($chat);

        $chat->messages()->delete();
        $chat->delete();

        return response()->json(['message' => 'Чат удалён']);
    }
}
