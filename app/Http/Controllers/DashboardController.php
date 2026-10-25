<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Report;
use App\Services\CompetitionSettings;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();
        $settings = app(CompetitionSettings::class);
        $activeRulesVersion = (string) $settings->get('rules_version', '1');

        $latestNda = $user->latestNdaSubmission;
        $isEmailVerified = $user->hasVerifiedEmail();
        $hasTeam = (bool) $team;
        $isRulesAccepted = $user->rules_accepted_version === $activeRulesVersion;
        $isNdaApproved = $latestNda && $latestNda->status === 'approved';

        $pinnedAnnouncements = Announcement::published()
            ->where('is_pinned', true)
            ->latest('published_at')
            ->take(3)
            ->get();

        $recentReports = collect();
        if ($team) {
            $recentReports = Report::where('team_id', $team->id)
                ->with(['scope', 'vulnerabilityType'])
                ->latest('submitted_at')
                ->take(5)
                ->get();
        }

        return view('dashboard', compact(
            'user',
            'team',
            'latestNda',
            'isEmailVerified',
            'hasTeam',
            'isRulesAccepted',
            'isNdaApproved',
            'pinnedAnnouncements',
            'recentReports',
            'activeRulesVersion'
        ));
    }
}
