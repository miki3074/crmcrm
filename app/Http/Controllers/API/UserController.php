<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;



class UserController extends Controller
{

public function managers(Request $request)
{
    $owner = auth()->user();
    $companyId = $request->query('company_id');

    $managers = collect();

    // 1) менеджеры из pivot company_user
    if ($companyId) {
        $company = \App\Models\Company::find($companyId);

        if ($company) {
            $managers = $company->users()
                ->wherePivot('role', 'manager')
                ->select('users.id', 'users.name')
                ->get();
        }
    }

    // 2) менеджеры, созданные владельцем
    $createdManagers = \App\Models\User::role('manager')
        ->where('created_by', $owner->id)
        ->select('id', 'name')
        ->get();

    $result = $managers->merge($createdManagers)->unique('id')->values();

    // 3) добавляем владельца
    if (! $result->contains('id', $owner->id)) {
        $result->prepend([
            'id'   => $owner->id,
            'name' => $owner->name,
        ]);
    }

    return response()->json($result->values());
}

public function generateTelegramToken(Request $request)
    {
        $user = $request->user();

        // создаём уникальный токен
        $token = Str::random(32);

        $user->telegram_token = $token;
        $user->save();

        return response()->json([
            'token' => $token,
            'link' => "https://t.me/".env('TELEGRAM_BOT_NAME')."?start={$token}",
            'instruction' => "Отправьте боту команду /start {$token}, чтобы привязать ваш Telegram."
        ]);
    }


public function saveChatId(Request $request)
{
    $request->validate([
        'chat_id' => 'required|string|max:50',
    ]);

    $user = $request->user();

    // 🔍 Проверяем, не занят ли chat_id другим пользователем
    $exists = \App\Models\User::where('telegram_chat_id', $request->chat_id)
        ->where('id', '!=', $user->id)
        ->exists();

    if ($exists) {
        return response()->json([
            'success' => false,
            'message' => '❌ Этот Telegram уже привязан к другому аккаунту.',
        ], 409); // 409 — Conflict
    }

    // ✅  сохраняем
    $user->telegram_chat_id = $request->chat_id;
    $user->save();

    return response()->json([
        'success' => true,
        'message' => '✅ Telegram успешно сохранён.',
        'chat_id' => $user->telegram_chat_id,
    ]);
}

}
