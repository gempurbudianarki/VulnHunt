<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    /**
     * Manajemen pengumuman di panel admin eksklusif hanya untuk peran admin.
     * Triager dan peserta dilarang melakukan CRUD pengumuman.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, Announcement $announcement): bool
    {
        return $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Announcement $announcement): bool
    {
        return $user->hasRole('admin');
    }
}
