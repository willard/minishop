<?php

namespace App\Policies;

use App\Models\User;

class MenuPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('menu.view');
    }

    public function update(User $user): bool
    {
        return $user->can('menu.update');
    }
}
