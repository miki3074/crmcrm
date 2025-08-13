<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use App\Models\User;

class EmployeeController extends Controller
{

public function index()
{
    abort_unless(auth()->user()->hasRole('admin'), 403, 'Forbidden');

    $companyIds = \App\Models\Company::where('user_id', auth()->id())->pluck('id');

    $users = User::whereIn('company_id', $companyIds)
        ->with(['roles:id,name','company:id,name'])
        ->get(['id','name','email','company_id']);

    return response()->json($users);
}


    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|confirmed|min:6',
        'role' => 'required|in:manager,employee',
        'company_id' => 'required|exists:companies,id',
    ]);

    // компания должна принадлежать текущему владельцу
    $isOwned = \App\Models\Company::where('id', $request->company_id)
        ->where('user_id', auth()->id())
        ->exists();

    abort_unless($isOwned, 403, 'Forbidden');

    $employee = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        'created_by' => auth()->id(),
        'company_id' => $request->company_id,
    ]);

    $employee->assignRole($request->role);

    return response()->json($employee->load('company:id,name'), 201);
}

}
