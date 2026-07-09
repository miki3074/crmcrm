<?php
// app/Http/Controllers/Admin/UserController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        // Проверка уже в middleware
        $users = User::with(['companies', 'roles'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $roles = Role::all();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        // Проверка уже в middleware
        $roles = Role::all();
        $companies = Company::all();

        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
            'companies' => $companies,
            'defaultRole' => 'admin'
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        // Проверка уже в middleware
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:' . User::class,
            'phone' => 'nullable|string|max:25',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Создаем пользователя
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        // 🔥 Всегда назначаем роль admin
        $user->assignRole('admin');

        // Если выбрана компания, прикрепляем пользователя к ней
        if ($request->company_id) {
            $company = Company::find($request->company_id);
            if ($company) {
                $company->users()->attach($user->id, [
                    'role' => 'admin',
                    'created_by' => auth()->id()
                ]);
            }
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь успешно создан!');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        // Проверка уже в middleware
        $user->load(['companies', 'roles']);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // Проверка уже в middleware
        $roles = Role::all();
        $companies = Company::all();
        $user->load(['companies', 'roles']);

        // Проверяем, что пользователь не пытается редактировать самого себя (если это miki23074@gmail.com)
        if ($user->id === auth()->id() && auth()->user()->email !== 'miki23074@gmail.com') {
            return redirect()->route('admin.users.index')
                ->with('error', 'Вы не можете редактировать самого себя');
        }

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
            'companies' => $companies
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        // Проверка уже в middleware

        // Запрещаем редактирование пользователя miki23074@gmail.com (кроме как самим собой)
        if ($user->email === 'miki23074@gmail.com' && auth()->user()->email !== 'miki23074@gmail.com') {
            return back()->with('error', 'Вы не можете редактировать этого пользователя');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:25',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|exists:roles,name',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Обновляем данные пользователя
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ];

        // Если указан новый пароль
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        $user->update($userData);

        // Обновляем роль
        $user->syncRoles([$request->role]);

        // Обновляем компанию
        if ($request->company_id) {
            $user->companies()->detach();
            $company = Company::find($request->company_id);
            if ($company) {
                $company->users()->attach($user->id, [
                    'role' => $request->role === 'manager' ? 'manager' : 'employee',
                    'created_by' => auth()->id()
                ]);
            }
        } else {
            $user->companies()->detach();
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь обновлен!');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Проверка уже в middleware

        // Запрещаем удаление пользователя miki23074@gmail.com
        if ($user->email === 'miki23074@gmail.com') {
            return back()->with('error', 'Вы не можете удалить этого пользователя');
        }

        // Не даем удалить самого себя
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Вы не можете удалить самого себя.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Пользователь удален!');
    }
}
