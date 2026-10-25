<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvExportService
{
    public function __construct(
        protected LeaderboardService $leaderboardService
    ) {}

    /**
     * Sanitasi sel CSV untuk mencegah CSV Formula Injection (DDE).
     * Sel yang diawali =, +, -, @, tab (\t), atau CR (\r) diberi awalan tanda kutip tunggal (').
     */
    public static function sanitizeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $str = (string) $value;
        if ($str === '') {
            return '';
        }

        $firstChar = $str[0];
        if (in_array($firstChar, ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $str;
        }

        return $str;
    }

    /**
     * Ekspor CSV data laporan: code, tim, scope, CWE, severity_final, status, poin, submitted_at, closed_at.
     * TANPA isi laporan (description, steps, impact, remediation) dan TANPA lampiran.
     */
    public function exportReports(?User $user = null): StreamedResponse
    {
        $filename = 'laporan-bugbounty-' . now()->format('Ymd-His') . '.csv';

        // Hitung baris untuk log
        $reportsCount = Report::count();

        // Catat aktivitas di spatie activity log
        $this->logExportActivity('reports', $reportsCount, $filename, $user);

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');

            // 1. Tulis UTF-8 BOM agar terbaca sempurna di MS Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // 2. Header CSV
            $headers = ['Code', 'Tim', 'Scope', 'CWE', 'Severity Final', 'Status', 'Poin', 'Submitted At (WIB)', 'Closed At (WIB)'];
            fputcsv($handle, array_map([self::class, 'sanitizeCell'], $headers));

            // 3. Streaming baris tanpa memuat seluruhnya ke RAM
            Report::with(['team', 'scope', 'vulnerabilityType'])
                ->orderBy('id', 'asc')
                ->chunk(100, function ($reports) use ($handle) {
                    foreach ($reports as $report) {
                        $submittedWib = $report->submitted_at
                            ? Carbon::parse($report->submitted_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                            : '-';
                        $closedWib = $report->closed_at
                            ? Carbon::parse($report->closed_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                            : '-';

                        $row = [
                            $report->code ?? ('VH-' . str_pad((string) $report->id, 5, '0', STR_PAD_LEFT)),
                            $report->team?->name ?? '-',
                            $report->scope?->name ?? '-',
                            $report->vulnerabilityType?->cwe_id ?? '-',
                            $report->severity_final ?? ($report->severity_claimed ?? '-'),
                            $report->status,
                            $report->points_awarded,
                            $submittedWib,
                            $closedWib,
                        ];

                        fputcsv($handle, array_map([self::class, 'sanitizeCell'], $row));
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    /**
     * Ekspor CSV data peringkat akhir papan peringkat.
     */
    public function exportLeaderboard(?User $user = null): StreamedResponse
    {
        $filename = 'peringkat-akhir-' . now()->format('Ymd-His') . '.csv';

        $rankings = $this->leaderboardService->getRankings();
        $rowCount = count($rankings);

        // Catat aktivitas di spatie activity log
        $this->logExportActivity('leaderboard', $rowCount, $filename, $user);

        return response()->streamDownload(function () use ($rankings) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fwrite($handle, "\xEF\xBB\xBF");

            $headers = ['Peringkat', 'Nama Tim', 'Total Poin', 'Jumlah Laporan Valid', 'Jumlah Critical', 'Jumlah High', 'Validasi Terakhir (WIB)'];
            fputcsv($handle, array_map([self::class, 'sanitizeCell'], $headers));

            foreach ($rankings as $row) {
                $validationWib = ! empty($row->latest_validation_at)
                    ? Carbon::parse($row->latest_validation_at)->timezone('Asia/Jakarta')->format('Y-m-d H:i:s')
                    : '-';

                $csvRow = [
                    $row->rank ?? '-',
                    $row->team_name ?? '-',
                    $row->total_points ?? 0,
                    $row->valid_reports_count ?? 0,
                    $row->critical_count ?? 0,
                    $row->high_count ?? 0,
                    $validationWib,
                ];

                fputcsv($handle, array_map([self::class, 'sanitizeCell'], $csvRow));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    protected function logExportActivity(string $type, int $rowCount, string $filename, ?User $user = null): void
    {
        if (function_exists('activity')) {
            try {
                $causer = $user ?? (auth()->check() ? auth()->user() : null);
                $activity = activity('export')
                    ->withProperties([
                        'type' => $type,
                        'row_count' => $rowCount,
                        'filename' => $filename,
                    ]);

                if ($causer) {
                    $activity->causedBy($causer);
                }

                $activity->log("Admin mengekspor data {$type} ke CSV ({$rowCount} baris)");
            } catch (\Throwable $e) {
                // Jangan gagalkan ekspor jika log gagal
            }
        }
    }
}
