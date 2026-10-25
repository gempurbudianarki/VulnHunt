<?php

namespace App\Policies;

use App\Models\NdaSubmission;
use App\Models\User;

class NdaSubmissionPolicy
{
    /**
     * Determine whether the user can view any NDA submissions.
     * Admin can view all NDAs in panel. Triager CANNOT view or verify NDAs.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view a specific NDA submission.
     */
    public function view(User $user, NdaSubmission $ndaSubmission): bool
    {
        // Admin bisa melihat NDA siapa saja
        if ($user->hasRole('admin')) {
            return true;
        }

        // Triager tidak memiliki hak akses verifikasi NDA
        if ($user->hasRole('triager')) {
            return false;
        }

        // Peserta hanya dapat melihat NDA miliknya sendiri
        return $user->id === $ndaSubmission->user_id;
    }

    /**
     * Determine whether the user can approve or reject (verify) the NDA.
     * Triager TIDAK BISA memverifikasi NDA (hak eksklusif admin).
     */
    public function update(User $user, NdaSubmission $ndaSubmission): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Alias for verifying NDA submission.
     */
    public function verify(User $user, NdaSubmission $ndaSubmission): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, NdaSubmission $ndaSubmission): bool
    {
        return false;
    }
}
