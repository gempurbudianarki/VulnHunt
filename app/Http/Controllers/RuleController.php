<?php

namespace App\Http\Controllers;

use App\Services\CompetitionSettings;
use Illuminate\Http\Request;

class RuleController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $settings = app(CompetitionSettings::class);
        $activeRulesVersion = (string) $settings->get('rules_version', '1');

        $isAccepted = $user->rules_accepted_version === $activeRulesVersion;

        return view('rules.index', compact(
            'user',
            'activeRulesVersion',
            'isAccepted'
        ));
    }

    public function accept(Request $request)
    {
        $user = $request->user();
        $settings = app(CompetitionSettings::class);
        $activeRulesVersion = (string) $settings->get('rules_version', '1');

        $request->validate([
            'agree' => ['required', 'accepted'],
        ], [
            'agree.accepted' => 'Anda harus mencentang persetujuan Aturan Main Lomba.',
        ]);

        $user->update([
            'rules_accepted_version' => $activeRulesVersion,
            'rules_accepted_at' => now(),
            'rules_accepted_ip' => $request->ip(),
        ]);

        return redirect()->route('rules.index')
            ->with('success', "Aturan Main Lomba (Versi {$activeRulesVersion}) berhasil disetujui!");
    }
}
