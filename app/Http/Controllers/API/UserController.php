<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{
    public function managers()
{
    $owner = auth()->user();

    $managers = User::role('manager')
        ->where('created_by', $owner->id)
        ->select('id', 'name')
        ->get();

    // Добавим самого себя (если его ещё нет в списке)
    if (!$managers->contains('id', $owner->id)) {
        $managers->prepend($owner->only(['id', 'name']));
    }

    return $managers->values(); // сброс ключей
}
}
