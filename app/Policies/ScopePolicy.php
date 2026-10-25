<?php

namespace App\Policies;

use App\Models\Scope;
use App\Models\User;

class ScopePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'triager']);
    }

    public function view(User $user, Scope $scope): bool
    {
        return $user->hasAnyRole(['admin', 'triager']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Scope $scope): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Scope $scope): bool
    {
        return $user->hasRole('admin');
    }
}
