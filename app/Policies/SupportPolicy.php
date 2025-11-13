<?php

namespace App\Policies;

use App\Models\User;

class SupportPolicy
{

public function viewAny(User $user)
    {
        return $user->hasRole('support');
    }

    public function viewMessages(User $user)
    {
        return $user->hasRole('support');
    }
}
