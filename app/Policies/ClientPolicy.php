<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    // список (можно просто фильтровать в контроллере, но оставим дозволение)
    public function viewAny(User $user): bool
    {
        return true;
    }

    // просмотр конкретного клиента
    public function view(User $user, Client $client): bool
    {
        return $user->id === $client->created_by
            || $user->id === $client->responsible_id;
    }

    // обновление
    public function update(User $user, Client $client): bool
    {
        return $user->id === $client->created_by
            || $user->id === $client->responsible_id;
    }

    // удаление — только создатель
    public function delete(User $user, Client $client): bool
    {
        return $user->id === $client->created_by;
    }
}
