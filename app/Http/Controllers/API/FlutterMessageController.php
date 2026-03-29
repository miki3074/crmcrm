<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FlutterMessage;
use App\Events\FlutterMessageSent;
use Illuminate\Http\Request;

class FlutterMessageController extends Controller
{
    // Получить историю сообщений
    public function index()
    {
        return FlutterMessage::with('user:id,name')
            ->latest()
            ->take(50)
            ->get()
            ->reversed()
            ->values();
    }

    // Отправить новое сообщение
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        // Сохраняем в базу
        $message = $request->user()->flutterMessages()->create([
            'message' => $request->message,
        ]);

        // Отправляем в Real-time
//        broadcast(new FlutterMessageSent($message))->toOthers();
        broadcast(new FlutterMessageSent($message));

        return response()->json($message->load('user:id,name'), 201);
    }
}
