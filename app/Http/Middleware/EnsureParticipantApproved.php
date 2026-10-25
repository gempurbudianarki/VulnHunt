<?php

namespace App\Http\Middleware;

use App\Services\CompetitionSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParticipantApproved
{
    /**
     * Handle an incoming request.
     * Gerbang akses per anggota: Email terverifikasi -> Punya tim -> Aturan versi terbaru disetujui -> NDA approved
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // 1. Verifikasi Email
        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        // 2. Wajib Memiliki Tim
        if (! $user->teamMember) {
            return redirect()->route('teams.index')
                ->with('error', 'Akses terkunci: Anda harus membuat atau bergabung dengan tim terlebih dahulu.');
        }

        // 3. Persetujuan Aturan Versi Terbaru
        $activeRulesVersion = (string) app(CompetitionSettings::class)->get('rules_version', '1');
        if ($user->rules_accepted_version !== $activeRulesVersion) {
            return redirect()->route('rules.index')
                ->with('error', 'Akses terkunci: Versi Aturan Main telah diperbarui. Silakan setujui Aturan Main terbaru.');
        }

        // 4. Verifikasi NDA (wajib status approved dari submission terbaru)
        $latestNda = $user->latestNdaSubmission;
        if (! $latestNda || $latestNda->status !== 'approved') {
            return redirect()->route('verification.index')
                ->with('error', 'Akses terkunci: Pengajuan NDA Anda belum disetujui oleh panitia.');
        }

        return $next($request);
    }
}
