<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any reports.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'triager', 'participant']);
    }

    /**
     * Determine whether the user can view the report.
     */
    public function view(User $user, Report $report): bool
    {
        // Staf admin dan triager dapat melihat semua laporan
        if ($user->hasAnyRole(['admin', 'triager'])) {
            return true;
        }

        // Peserta hanya dapat melihat laporan milik timnya sendiri
        $team = $user->currentTeam();

        return $team !== null && $team->id === $report->team_id;
    }

    /**
     * Determine whether the user can create a new report.
     */
    public function create(User $user): bool
    {
        // Staf tidak mengirim laporan kerentanan peserta
        if ($user->hasAnyRole(['admin', 'triager'])) {
            return false;
        }

        // Peserta wajib sudah disetujui (email, aturan, NDA approved) dan punya tim
        return $user->isApprovedParticipant() && $user->currentTeam() !== null;
    }

    /**
     * Determine whether the user can update the report.
     */
    public function update(User $user, Report $report): bool
    {
        // Staf admin dan triager dapat meninjau dan memperbarui status
        if ($user->hasAnyRole(['admin', 'triager'])) {
            return true;
        }

        // Peserta HANYA bisa merevisi saat status 'needs_info' dan merupakan anggota tim pemilik
        if ($report->status === 'needs_info') {
            $team = $user->currentTeam();

            return $team !== null && $team->id === $report->team_id;
        }

        // Selain needs_info, laporan terkirim TIDAK BISA diedit bebas oleh peserta
        return false;
    }

    /**
     * Determine whether the user can triage / change status of the report.
     */
    public function triage(User $user, Report $report): bool
    {
        return $user->hasAnyRole(['admin', 'triager']);
    }

    /**
     * Determine whether the user can reopen a resolved decision.
     */
    public function reopen(User $user, Report $report): bool
    {
        return $user->hasAnyRole(['admin', 'triager']);
    }

    /**
     * Determine whether the user can download attachments of the report.
     */
    public function downloadAttachment(User $user, Report $report): bool
    {
        if ($user->hasAnyRole(['admin', 'triager'])) {
            return true;
        }

        $team = $user->currentTeam();

        return $team !== null && $team->id === $report->team_id;
    }

    /**
     * Determine whether the user can add internal comments.
     */
    public function addInternalComment(User $user, Report $report): bool
    {
        return $user->hasAnyRole(['admin', 'triager']);
    }

    /**
     * Reports cannot be deleted.
     */
    public function delete(User $user, Report $report): bool
    {
        return false;
    }
}
