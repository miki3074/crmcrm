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

    // Берём только компании текущего владельца
    $companies = Company::where('user_id', $ownerId)->pluck('id');

    // Пользователи через pivot company_user,
    // но только для этих компаний
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
    $request->validate([
        'name'       => 'required|string|max:255',
        'email'      => 'required|email|unique:users,email',
        'password'   => 'required|confirmed|min:6',
        'role'       => 'required|in:manager,employee',
        'company_id' => 'required|exists:companies,id',
    ]);

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


public function usersForAttach()
    {
        $ownerId = auth()->id();

        // компании, которыми владеет текущий админ
        $companyIds = Company::where('user_id', $ownerId)->pluck('id')->toArray();

        // если у владельца нет компаний — вернуть пустой список
        if (empty($companyIds)) {
            return response()->json([], 200);
        }

        // id пользователей, уже прикреплённых к этим компаниям через pivot company_user
        $attachedUserIds = \DB::table('company_user')
            ->whereIn('company_id', $companyIds)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // исключаем текущего владельца и уже прикреплённых
        $excluded = array_merge([$ownerId], $attachedUserIds);

        $users = User::whereNotIn('id', $excluded)
            ->select(['id','name','email'])
            ->get();

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
    ]);

    $user = \DB::table('company_user')
        ->where('user_id', $id)
        ->whereIn('company_id', Company::where('user_id', auth()->id())->pluck('id'))
        ->first();

    if (!$user) {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    \DB::table('company_user')
        ->where('user_id', $id)
        ->update(['role' => $request->role, 'updated_at' => now()]);

    return response()->json(['message' => 'Role updated']);
}



}
