<?php

namespace App\Services;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamJoinService
{
    public function __construct(
        protected CompetitionSettings $settings
    ) {}

    /**
     * Memproses peserta bergabung ke tim dengan pessimistic row locking
     * untuk mencegah race condition (melebihi batas max_team_size).
     */
    public function join(User $user, string $inviteCode): Team
    {
        // 1. Cek apakah user sudah memiliki tim
        if (TeamMember::where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'invite_code' => ['Anda sudah terdaftar dalam sebuah tim.'],
            ]);
        }

        $maxTeamSize = (int) $this->settings->get('max_team_size', 4);

        return DB::transaction(function () use ($user, $inviteCode, $maxTeamSize) {
            $team = Team::where('invite_code', strtoupper(trim($inviteCode)))
                ->lockForUpdate()
                ->first();

            if (! $team) {
                throw ValidationException::withMessages([
                    'invite_code' => ['Kode undangan tim tidak ditemukan.'],
                ]);
            }

            if ($team->isDisqualified()) {
                throw ValidationException::withMessages([
                    'invite_code' => ['Tim ini telah didiskualifikasi dari perlombaan.'],
                ]);
            }

            $currentMembersCount = TeamMember::where('team_id', $team->id)->lockForUpdate()->count();
            if ($currentMembersCount >= $maxTeamSize) {
                throw ValidationException::withMessages([
                    'invite_code' => ["Tim ini sudah penuh (maksimal {$maxTeamSize} anggota)."],
                ]);
            }

            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'member',
                'joined_at' => now(),
            ]);

            return $team;
        });
    }
}
