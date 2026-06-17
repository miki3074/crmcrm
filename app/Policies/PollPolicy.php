<?php

namespace App\Policies;

use App\Models\Poll;
use App\Models\User;

class PollPolicy
{
    public function view(User $user, Poll $poll): bool
    {
        return $poll->company_id === $user->company_id;
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null;
    }

    public function close(User $user, Poll $poll): bool
    {
        return $poll->created_by === $user->id || $user->role === 'owner';
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $poll->created_by === $user->id || $user->role === 'owner';
    }
}
