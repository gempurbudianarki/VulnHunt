<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public const CACHE_KEY = 'vulnhunt_leaderboard_rankings';

    public const CACHE_TTL = 3600; // 1 jam, di-flush seketika saat ada mutasi

    public function __construct(
        protected CompetitionSettings $settings
    ) {}

    /**
     * Memeriksa apakah papan peringkat dapat dilihat secara publik oleh peserta umum.
     */
    public function isPubliclyVisible(): bool
    {
        $mode = $this->settings->get('leaderboard_mode', 'visible');

        if ($mode === 'visible') {
            return true;
        }

        if ($mode === 'hidden') {
            return false;
        }

        if ($mode === 'hidden_until_end') {
            $closeAt = $this->settings->get('submission_close_at');
            if ($closeAt === null || $closeAt === '') {
                return false;
            }

            return now()->utc()->gt(Carbon::parse($closeAt, 'UTC')->utc());
        }

        return false;
    }

    /**
     * Memeriksa apakah papan peringkat dapat dilihat oleh pengguna.
     * Admin selalu dapat melihat papan peringkat dalam mode apa pun.
     */
    public function isVisible(?User $user = null): bool
    {
        if ($user && $user->hasRole('admin')) {
            return true;
        }

        return $this->isPubliclyVisible();
    }

    /**
     * Mengambil daftar peringkat agregat menggunakan shared cache.
     *
     * @return Collection<int, object{
     *     team_id: int,
     *     team_name: string,
     *     total_points: int,
     *     valid_reports_count: int,
     *     critical_count: int,
     *     high_count: int,
     *     latest_validation_at: string|null
     * }>
     */
    public function getRankings(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->queryAggregatedRankings();
        });
    }

    /**
     * Query agregat SQL murni (bebas N+1) dengan urutan tie-break 4 tingkat:
     * 1. Total poin DESC
     * 2. Jumlah critical DESC
     * 3. Jumlah high DESC
     * 4. Waktu validasi terakhir ASC (paling awal menang)
     * 5. Team ID ASC (deterministik)
     * Tim diskualifikasi disaring secara mutlak.
     */
    public function queryAggregatedRankings(): Collection
    {
        return DB::table('teams')
            ->leftJoin('reports', function ($join) {
                $join->on('teams.id', '=', 'reports.team_id')
                    ->whereIn('reports.status', ['valid', 'rewarded']);
            })
            ->where('teams.status', '!=', 'disqualified')
            ->select([
                'teams.id as team_id',
                'teams.name as team_name',
                DB::raw('COALESCE(SUM(reports.points_awarded), 0) as total_points'),
                DB::raw('COUNT(reports.id) as valid_reports_count'),
                DB::raw("COUNT(CASE WHEN reports.severity_final = 'critical' THEN 1 END) as critical_count"),
                DB::raw("COUNT(CASE WHEN reports.severity_final = 'high' THEN 1 END) as high_count"),
                DB::raw('MAX(reports.closed_at) as latest_validation_at'),
            ])
            ->groupBy('teams.id', 'teams.name')
            ->orderByDesc('total_points')
            ->orderByDesc('critical_count')
            ->orderByDesc('high_count')
            ->orderByRaw('CASE WHEN MAX(reports.closed_at) IS NULL THEN 1 ELSE 0 END ASC')
            ->orderBy('latest_validation_at', 'asc')
            ->orderBy('teams.id', 'asc')
            ->get()
            ->map(function ($row) {
                return (object) [
                    'team_id' => (int) $row->team_id,
                    'team_name' => (string) $row->team_name,
                    'total_points' => (int) $row->total_points,
                    'valid_reports_count' => (int) $row->valid_reports_count,
                    'critical_count' => (int) $row->critical_count,
                    'high_count' => (int) $row->high_count,
                    'latest_validation_at' => $row->latest_validation_at,
                ];
            });
    }

    /**
     * Titik tunggal invalidasi cache papan peringkat.
     */
    public function invalidateCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
