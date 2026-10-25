<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Scope;
use App\Models\VulnerabilityType;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();

        $reports = collect();
        if ($team) {
            $reports = Report::where('team_id', $team->id)
                ->with(['scope', 'vulnerabilityType'])
                ->latest('submitted_at')
                ->get();
        }

        return view('reports.index', compact('team', 'reports'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();

        $scopes = Scope::where('scope_status', 'in_scope')->where('is_active', true)->get();
        $vulnerabilityTypes = VulnerabilityType::where('is_active', true)->get();

        return view('reports.create', compact('team', 'scopes', 'vulnerabilityTypes'));
    }

    public function show(Request $request, string $code)
    {
        $user = $request->user();
        $team = $user->currentTeam();

        $report = Report::where('code', $code)
            ->where('team_id', $team?->id)
            ->firstOrFail();

        return view('reports.show', compact('report', 'team'));
    }
}
