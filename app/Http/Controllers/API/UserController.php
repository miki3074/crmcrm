<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{
// app/Http/Controllers/API/UserController.php

public function managers(Request $request)
{
    $owner = auth()->user();
    $companyId = $request->query('company_id');

    $managers = collect();

    // 1) если пришёл company_id — берем менеджеров из pivot company_user с role = 'manager'
    if ($companyId) {
        $company = \App\Models\Company::with(['users' => function($q) {
            // ничего тут не фильтруем — фильтруем ниже по pivot
        }])->find($companyId);

        if ($company) {
            $managers = $company->users()
                ->wherePivot('role', 'manager')
                ->select('users.id', 'users.name')
                ->get();
        }
    }

    // 2) доп. — менеджеры, созданные владельцем (legacy / удобно)
    $createdManagers = \App\Models\User::role('manager')
        ->where('created_by', $owner->id)
        ->select('id', 'name')
        ->get();

    // объединяем, убираем дубликаты, сбрасываем ключи
    $result = $managers->merge($createdManagers)->unique('id')->values();

    // 3) гарантированно добавляем самого владельца в начало (если нужно)
    if (! $result->contains('id', $owner->id)) {
        $result->prepend(collect($owner->only(['id','name'])));
        $result = $result->flatten(1); // в случае коллекций в коллекции
    }

    return response()->json($result);
}

}
