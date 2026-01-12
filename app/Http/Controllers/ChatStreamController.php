<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\ChatMessage;

class ChatStreamController extends Controller
{
    public function stream(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|integer',
            'message' => 'required|string|max:2000',
        ]);

        $chat = Chat::where('id', $request->chat_id)
            ->where(fn($q) =>
            $q->where('user_id', auth()->id())
                ->orWhere('session_id', session()->getId())
            )->firstOrFail();


        ChatMessage::create([
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => $request->message,
        ]);


        $history = $chat->messages()
            ->orderBy('id')
            ->get()
            ->map(fn($m) => [
                'role' => $m->role,
                'content' => $m->content,
            ])
            ->toArray();


        if (empty($history)) {
            $history[] = ['role' => 'user', 'content' => $request->message];
        }


        $payload = [
            'model' => 'gpt-4.1-mini',
            'messages' => $history,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . config('services.openai.key'),
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);

        $result = curl_exec($ch);

        if ($result === false) {
            $error = curl_error($ch);
            curl_close($ch);
            return response()->json(['content' => "(Ошибка OpenAI: $error)"], 500);
        }
        curl_close($ch);

        $json = json_decode($result, true);


        $assistantText = $json['choices'][0]['message']['content'] ?? '(Нет ответа от GPT)';


        ChatMessage::create([
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => $assistantText,
        ]);

        return response()->json(['content' => $assistantText]);
    }
}
