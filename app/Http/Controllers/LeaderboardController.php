<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Services\CompetitionSettings;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $settings = app(CompetitionSettings::class);
        $mode = $settings->get('leaderboard_mode', 'visible');
        $isAdmin = $request->user()->hasRole('admin');

        $teams = Team::where('status', 'active')->get();

        return view('leaderboard.index', compact('teams', 'mode', 'isAdmin'));
    }
}
