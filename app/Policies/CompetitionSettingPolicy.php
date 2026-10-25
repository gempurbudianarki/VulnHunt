<?php

namespace App\Policies;

use App\Models\CompetitionSetting;
use App\Models\User;

class CompetitionSettingPolicy
{
    /**
     * Triager TIDAK BISA mengubah atau mengelola pengaturan lomba (hak eksklusif admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, CompetitionSetting $setting): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, CompetitionSetting $setting): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, CompetitionSetting $setting): bool
    {
        return false;
    }
}
