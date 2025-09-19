<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;



class UserController extends Controller
{
// app/Http/Controllers/API/UserController.php

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

    // объединяем
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
    $user->telegram_chat_id = $request->chat_id;
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Telegram ID сохранён',
        'chat_id' => $user->telegram_chat_id,
    ]);
}
   


}
