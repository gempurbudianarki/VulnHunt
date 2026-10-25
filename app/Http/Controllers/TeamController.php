<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Services\CompetitionSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();
        $settings = app(CompetitionSettings::class);
        $activeRulesVersion = (string) $settings->get('rules_version', '1');

        $minTeamSize = (int) $settings->get('min_team_size', 2);
        $maxTeamSize = (int) $settings->get('max_team_size', 4);

        if ($team) {
            $team->load([
                'members.user.latestNdaSubmission',
                'leader',
            ]);
        }

        return view('teams.index', compact(
            'user',
            'team',
            'activeRulesVersion',
            'minTeamSize',
            'maxTeamSize'
        ));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // 1. Cek apakah user sudah memiliki tim
        if (TeamMember::where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Anda sudah terdaftar dalam sebuah tim.');
        }

        // 2. Validasi input nama tim
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:50', 'unique:teams,name'],
        ], [
            'name.unique' => 'Nama tim ini sudah digunakan oleh tim lain.',
            'name.min' => 'Nama tim minimal 3 karakter.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $team = Team::create([
                'name' => trim($validated['name']),
                'leader_id' => $user->id,
                'status' => 'active',
            ]);

            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'leader',
                'joined_at' => now(),
            ]);
        });

        return redirect()->route('teams.index')
            ->with('success', 'Tim berhasil dibentuk! Anda otomatis menjadi Ketua Tim.');
    }

    public function join(Request $request)
    {
        $user = $request->user();

        // 1. Cek apakah user sudah memiliki tim
        if (TeamMember::where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Anda sudah terdaftar dalam sebuah tim.');
        }

        $validated = $request->validate([
            'invite_code' => ['required', 'string', 'size:8'],
        ]);

        $settings = app(CompetitionSettings::class);
        $maxTeamSize = (int) $settings->get('max_team_size', 4);

        return DB::transaction(function () use ($user, $validated, $maxTeamSize) {
            $team = Team::where('invite_code', strtoupper(trim($validated['invite_code'])))
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

            if ($team->members()->count() >= $maxTeamSize) {
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

            return redirect()->route('teams.index')
                ->with('success', "Berhasil bergabung dengan tim {$team->name}!");
        });
    }

    public function leave(Request $request)
    {
        $user = $request->user();
        $membership = $user->teamMember;

        if (! $membership) {
            return back()->with('error', 'Anda tidak terdaftar dalam tim mana pun.');
        }

        $team = $membership->team;

        // Spesifikasi Bagian 9: Anggota bisa keluar dari tim selama tim belum memiliki laporan.
        if ($team->reports()->count() > 0) {
            return back()->with('error', 'Tidak dapat keluar dari tim karena tim sudah memiliki riwayat laporan.');
        }

        if ($membership->isLeader() && $team->members()->count() > 1) {
            return back()->with('error', 'Sebagai ketua tim, Anda harus menyerahkan kepemimpinan sebelum keluar atau membubarkan tim.');
        }

        DB::transaction(function () use ($membership, $team) {
            $membership->delete();
            // Jika ketua keluar dan tidak ada anggota lain, hapus tim
            if ($team->members()->count() === 0) {
                $team->delete();
            }
        });

        return redirect()->route('teams.index')
            ->with('success', 'Anda telah berhasil keluar dari tim.');
    }

    public function removeMember(Request $request, int $userId)
    {
        $caller = $request->user();
        $callerMembership = $caller->teamMember;

        if (! $callerMembership || ! $callerMembership->isLeader()) {
            return back()->with('error', 'Hanya ketua tim yang berhak mengeluarkan anggota.');
        }

        $team = $callerMembership->team;

        if ($team->reports()->count() > 0) {
            return back()->with('error', 'Tidak dapat mengeluarkan anggota karena tim sudah memiliki riwayat laporan.');
        }

        $targetMember = TeamMember::where('team_id', $team->id)
            ->where('user_id', $userId)
            ->first();

        if (! $targetMember) {
            return back()->with('error', 'Anggota tidak ditemukan dalam tim Anda.');
        }

        if ($targetMember->isLeader()) {
            return back()->with('error', 'Ketua tim tidak dapat dikeluarkan.');
        }

        $targetMember->delete();

        return back()->with('success', 'Anggota tim berhasil dikeluarkan.');
    }

    public function regenerateInviteCode(Request $request)
    {
        $caller = $request->user();
        $callerMembership = $caller->teamMember;

        if (! $callerMembership || ! $callerMembership->isLeader()) {
            return back()->with('error', 'Hanya ketua tim yang berhak membuat ulang kode undangan.');
        }

        $team = $callerMembership->team;
        $team->update([
            'invite_code' => Team::generateUniqueInviteCode(),
        ]);

        return back()->with('success', 'Kode undangan tim berhasil diperbarui!');
    }
}
