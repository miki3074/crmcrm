<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Validation\Rule;
use App\Models\Company;

class EmployeeController extends Controller
{

public function index()
{
    abort_unless(auth()->user()->hasRole('admin'), 403, 'Forbidden');

    $ownerId = auth()->id();


    $companies = Company::where('user_id', $ownerId)->pluck('id');

    
    $users = \DB::table('company_user')
        ->join('users', 'company_user.user_id', '=', 'users.id')
        ->join('companies', 'company_user.company_id', '=', 'companies.id')
        ->whereIn('companies.id', $companies)
        ->select(
            'users.id',
            'users.name',
            'users.email',
            'companies.id as company_id',
            'companies.name as company_name',
            'company_user.role',
            'company_user.created_by'
        )
        ->get()
        ->map(function ($row) {
            return [
                'id'    => $row->id,
                'name'  => $row->name,
                'email' => $row->email,
                'company' => [
                    'id'   => $row->company_id,
                    'name' => $row->company_name,
                ],
                'role'       => $row->role,
                'created_by' => $row->created_by,
            ];
        });

    return response()->json($users->values());
}



    public function store(Request $request)
{

 $messages = [
        'name.required' => 'Введите имя.',
        'name.max' => 'Имя не должно превышать :max символов.',

        'email.required' => 'Введите email.',
        'email.email' => 'Введите корректный email.',
        'email.unique' => 'Этот email уже зарегистрирован.',

        'password.required' => 'Введите пароль.',
        'password.confirmed' => 'Пароли не совпадают.',
        'password.min' => 'Пароль должен быть не короче :min символов.',

        'role.required' => 'Выберите роль.',
        'role.in' => 'Роль указана неверно.',

        'company_id.required' => 'Выберите компанию.',
        'company_id.exists' => 'Выбранная компания не найдена или вам недоступна.',
    ];

    $request->validate([
        'name'       => 'required|string|max:255',
        'email'      => 'required|email|unique:users,email',
        'password'   => 'required|confirmed|min:6',
        'role'       => 'required|in:manager,employee',
        'company_id' => 'required|exists:companies,id',
    ], $messages);

    // проверяем, что компания принадлежит текущему владельцу
    $company = \App\Models\Company::where('id', $request->company_id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    // создаём нового пользователя
    $employee = User::create([
        'name'       => $request->name,
        'email'      => $request->email,
        'password'   => \Illuminate\Support\Facades\Hash::make($request->password),
        'created_by' => auth()->id(),
    ]);

    // вместо assignRole — пишем в pivot company_user
    $company->users()->attach($employee->id, [
        'role'       => $request->role,
        'created_by' => auth()->id(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return response()->json(
        $employee->load(['companies' => fn($q) => $q->where('id', $company->id)]),
        201
    );
}


public function usersForAttach(Request $request)
{
    $ownerId = auth()->id();

    // проверим, что компания_id передана
    $companyId = $request->query('company_id');

    if (!$companyId) {
        return response()->json(['error' => 'company_id is required'], 400);
    }

    // убедимся, что компания принадлежит текущему владельцу
    $company = Company::where('id', $companyId)
        ->where('user_id', $ownerId)
        ->firstOrFail();

    // пользователи, уже прикреплённые к ЭТОЙ компании
    $attachedUserIds = \DB::table('company_user')
        ->where('company_id', $companyId)
        ->pluck('user_id')
        ->unique()
        ->toArray();

    $excluded = array_merge([$ownerId], $attachedUserIds);

    // 🔍 Поиск
    $query = User::query()
        ->whereNotIn('id', $excluded)
        ->select(['id', 'name', 'email']);

    if ($request->filled('q')) {
        $q = $request->q;
        $query->where(function ($sub) use ($q) {
            $sub->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
        });
    }

    $users = $query->limit(10)->get();

    return response()->json($users);
}



    /**
     * Пример attach (если ещё нет) — привязывает существующего юзера к компании через pivot
     */
    public function attach(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'company_id' => 'required|exists:companies,id',
            'role' => 'required|in:owner,manager,employee',
        ]);

        // проверка, что компания принадлежит текущему владельцу
        $company = Company::where('id', $request->company_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $user = User::findOrFail($request->user_id);

        // привязываем в pivot, не меняя глобальную роль Spatie
        $user->attachedCompanies()->syncWithoutDetaching([
            $company->id => [
                'role' => $request->role,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        return response()->json(['message' => 'User attached'], 200);
    }


    public function ownerCompanies()
{
    $ownerId = auth()->id();

    // только компании, где текущий пользователь владелец
    $companies = Company::where('user_id', $ownerId)
        ->select(['id','name'])
        ->get();

    return response()->json($companies);
}


public function updateRole(Request $request, $id)
{
    $request->validate([
        'role' => 'required|in:manager,employee',
        'company_id' => 'required|exists:companies,id',
    ]);

    $ownerId = auth()->id();

    // проверяем, что компания принадлежит текущему владельцу
    $company = Company::where('id', $request->company_id)
        ->where('user_id', $ownerId)
        ->firstOrFail();

    // проверяем, что пользователь действительно в этой компании
    $exists = \DB::table('company_user')
        ->where('user_id', $id)
        ->where('company_id', $company->id)
        ->exists();

    if (!$exists) {
        return response()->json(['message' => 'User not found in this company'], 404);
    }

    // обновляем только для этой компании
    \DB::table('company_user')
        ->where('user_id', $id)
        ->where('company_id', $company->id)
        ->update([
            'role' => $request->role,
            'updated_at' => now(),
        ]);

    return response()->json(['message' => 'Role updated']);
}




}
