<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    /**
     * Проверка доступа: только суперпользователь
     */
    private function ensureAdminAccess()
    {
        if (auth()->user()->email !== 'miki23074@gmail.com') {
            abort(403, 'Доступ запрещён');
        }
    }

    /**
     * Получить всех пользователей
     */
    public function index()
    {
         return User::select('id', 'name', 'email', 'created_at')
        ->where('id', '<>', auth()->id()) // 👈 исключаем текущего пользователя
        ->orderBy('id', 'asc')
        ->get();
    }

    /**
     * Обновить данные пользователя
     */
    public function update(Request $request, User $user)
    {
        $this->ensureAdminAccess();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|min:6',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json(['message' => 'Данные пользователя обновлены', 'user' => $user]);
    }

    /**
     * Удалить пользователя
     */
    public function destroy(User $user)
{
    $this->ensureAdminAccess();

    // 🚫 запретить удалять самого себя
    if ($user->id === auth()->id()) {
        return response()->json(['message' => 'Вы не можете удалить сами себя'], 403);
    }

    // 🚫 запретить удалить администратора (на всякий случай)
    if ($user->email === 'miki23074@gmail.com') {
        return response()->json(['message' => 'Нельзя удалить администратора'], 403);
    }

    $user->delete();

    return response()->json(['message' => 'Пользователь удалён']);
}

}
