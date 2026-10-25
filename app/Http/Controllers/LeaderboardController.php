<?php

namespace App\Http\Controllers;

use App\Services\LeaderboardService;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboardService)
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('admin');
        $isVisible = $leaderboardService->isVisible($user);
        $isPubliclyVisible = $leaderboardService->isPubliclyVisible();

        // Keamanan Kebocoran Data (Information Disclosure Protection):
        // Jika mode hidden atau hidden_until_end dan user bukan admin,
        // TIDAK ADA data peringkat yang dimuat ke memori, dipassing ke view, respons JSON, maupun Livewire.
        $rankings = $isVisible ? $leaderboardService->getRankings() : collect();

        if ($request->wantsJson()) {
            if (! $isVisible) {
                return response()->json([
                    'message' => 'Papan peringkat sedang disembunyikan oleh panitia.',
                    'is_visible' => false,
                    'data' => [],
                ], 403);
            }

            return response()->json([
                'is_visible' => true,
                'data' => $rankings,
            ]);
        }

        return view('leaderboard.index', compact('rankings', 'isVisible', 'isPubliclyVisible', 'isAdmin'));
    }
}
