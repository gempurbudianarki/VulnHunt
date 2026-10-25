<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\ReportVersion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportSubmissionService
{
    public function __construct(
        protected CompetitionSettings $settings
    ) {}

    /**
     * Submit a new vulnerability report with concurrency protection and quota invariants.
     *
     * @param  array{
     *     scope_id: int,
     *     vulnerability_type_id: int,
     *     title: string,
     *     endpoint: string,
     *     parameter?: string|null,
     *     description: string,
     *     steps_to_reproduce: string,
     *     impact: string,
     *     remediation?: string|null,
     * }  $data
     * @param  array{
     *     vector: string,
     *     score: float,
     *     severity: string,
     * }  $cvss
     */
    public function submit(User $user, Team $userTeam, array $data, array $cvss): Report
    {
        return DB::transaction(function () use ($user, $userTeam, $data, $cvss) {
            $team = Team::where('id', $userTeam->id)->lockForUpdate()->firstOrFail();

            if (! $this->settings->isSubmissionOpen()) {
                throw ValidationException::withMessages([
                    'title' => ['Pengiriman laporan saat ini sedang ditutup oleh panitia.'],
                ]);
            }

            if ($team->isDisqualified()) {
                throw ValidationException::withMessages([
                    'title' => ['Tim Anda telah didiskualifikasi dari perlombaan.'],
                ]);
            }

            // Invarian Kuota: Cek kuota laporan aktif (submitted + in_review) dengan locking read
            $activeCount = Report::where('team_id', $team->id)
                ->whereIn('status', ['submitted', 'in_review'])
                ->lockForUpdate()
                ->count();
            $effectiveQuota = $team->effectiveQuota();

            if ($activeCount >= $effectiveQuota) {
                throw ValidationException::withMessages([
                    'title' => ["Tim Anda telah mencapai batas kuota laporan aktif ({$activeCount}/{$effectiveQuota})."],
                ]);
            }

            // Cek batas total laporan jika dikonfigurasi
            $maxTotal = $this->settings->get('max_total_reports_per_team');
            if ($maxTotal !== null && $team->reports()->count() >= (int) $maxTotal) {
                throw ValidationException::withMessages([
                    'title' => ["Tim Anda telah mencapai batas total laporan ({$maxTotal} laporan)."],
                ]);
            }

            // Buat record Laporan (submitted_at tidak diisi dari input, diisi otomatis oleh server)
            $report = Report::create([
                'team_id' => $team->id,
                'reported_by' => $user->id,
                'scope_id' => (int) $data['scope_id'],
                'vulnerability_type_id' => (int) $data['vulnerability_type_id'],
                'title' => trim($data['title']),
                'endpoint' => trim($data['endpoint']),
                'parameter' => ! empty($data['parameter']) ? trim($data['parameter']) : null,
                'cvss_vector_claimed' => $cvss['vector'],
                'cvss_score_claimed' => $cvss['score'],
                'severity_claimed' => $cvss['severity'],
                'description' => $data['description'],
                'steps_to_reproduce' => $data['steps_to_reproduce'],
                'impact' => $data['impact'],
                'remediation' => $data['remediation'] ?? null,
                'status' => 'submitted',
                'revision_no' => 1,
            ]);

            // Catat Riwayat Status Awal
            ReportStatusHistory::create([
                'report_id' => $report->id,
                'from_status' => null,
                'to_status' => 'submitted',
                'changed_by' => $user->id,
                'note' => 'Pengiriman awal laporan kerentanan.',
            ]);

            // Catat Snapshot Versi 1
            ReportVersion::create([
                'report_id' => $report->id,
                'revision_no' => 1,
                'snapshot' => [
                    'title' => $report->title,
                    'description' => $data['description'],
                    'steps_to_reproduce' => $data['steps_to_reproduce'],
                    'impact' => $data['impact'],
                    'remediation' => $data['remediation'] ?? null,
                    'cvss_vector' => $cvss['vector'],
                    'cvss_score' => $cvss['score'],
                    'severity' => $cvss['severity'],
                    'change_summary' => 'Versi awal pengajuan laporan.',
                ],
                'created_by' => $user->id,
            ]);

            return $report;
        });
    }
}
