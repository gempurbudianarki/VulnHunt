<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportComment;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Notifications\ReportStatusChangedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ReportStateMachine
{
    /**
     * Matriks transisi status yang sah sesuai Spesifikasi Bagian 5.3:
     * - submitted -> in_review
     * - in_review -> needs_info, valid, invalid, duplicate
     * - needs_info -> in_review (balasan/revisi tim)
     * - valid -> rewarded
     * - valid, invalid, duplicate -> in_review (reopen keputusan oleh admin)
     */
    protected const ALLOWED_TRANSITIONS = [
        'submitted' => ['in_review'],
        'in_review' => ['needs_info', 'valid', 'invalid', 'duplicate'],
        'needs_info' => ['in_review'],
        'valid' => ['rewarded', 'in_review'],
        'invalid' => ['in_review'],
        'duplicate' => ['in_review'],
        'rewarded' => ['in_review'],
    ];

    public function __construct(
        protected PointsCalculator $pointsCalculator
    ) {}

    /**
     * Memeriksa apakah suatu transisi status diizinkan secara teknis.
     */
    public function canTransition(string $fromStatus, string $toStatus): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$fromStatus] ?? [];

        return in_array($toStatus, $allowed, true);
    }

    /**
     * Menjalankan transisi status laporan dengan penegakan aturan server,
     * transaksi database, pencatatan audit riwayat, dan notifikasi.
     *
     * @param  array{
     *     reason?: string|null,
     *     cvss_vector_final?: string|null,
     *     quality_factor?: float|null,
     *     points_override?: int|null,
     *     points_override_note?: string|null,
     *     duplicate_of?: int|null,
     *     is_team_reply?: bool
     * }  $params
     */
    public function transition(Report $report, string $toStatus, User $actor, array $params = []): Report
    {
        $fromStatus = $report->status;
        $isTeamReply = (bool) ($params['is_team_reply'] ?? false);

        // 1. Verifikasi Otorisasi Peran
        if (! $actor->hasAnyRole(['admin', 'triager'])) {
            // Hanya transisi needs_info -> in_review yang boleh dipicu oleh anggota tim pelapor
            if (! ($fromStatus === 'needs_info' && $toStatus === 'in_review' && $isTeamReply)) {
                throw new AuthorizationException('Hanya staf (admin atau triager) yang memiliki wewenang untuk mengubah status laporan.');
            }

            // Pastikan peserta memang anggota dari tim pemilik laporan
            $userTeam = $actor->currentTeam();
            if (! $userTeam || $userTeam->id !== $report->team_id) {
                throw new AuthorizationException('Anda tidak berhak memperbarui status laporan tim lain.');
            }
        }

        // 2. Validasi Keabsahan Transisi State Machine
        if (! $this->canTransition($fromStatus, $toStatus)) {
            throw new InvalidArgumentException(
                "Transisi status laporan dari '{$fromStatus}' ke '{$toStatus}' tidak sah dan ditolak oleh sistem."
            );
        }

        // 3. Eksekusi Perubahan di Dalam Transaksi Database
        return DB::transaction(function () use ($report, $fromStatus, $toStatus, $actor, $params, $isTeamReply) {
            // Lock baris laporan untuk mencegah race condition
            $lockedReport = Report::where('id', $report->id)->lockForUpdate()->firstOrFail();

            $reason = trim($params['reason'] ?? '');
            $auditNote = $reason;

            $updateData = [
                'status' => $toStatus,
            ];

            // Penanganan Kasus Tiap Transisi
            switch ($toStatus) {
                case 'in_review':
                    if (in_array($fromStatus, ['valid', 'invalid', 'duplicate', 'rewarded'], true)) {
                        // REOPEN KEPUTUSAN: Wajib menyertakan alasan!
                        if (empty($reason)) {
                            throw new InvalidArgumentException('Membuka kembali keputusan laporan wajib disertai dengan catatan alasan.');
                        }

                        // Reset poin & tanggal penutupan saat laporan dibuka kembali (Spesifikasi 8.4)
                        $updateData['points_awarded'] = 0;
                        $updateData['closed_at'] = null;
                        $auditNote = "Membuka kembali keputusan ({$fromStatus} -> in_review). Alasan: {$reason}";
                    } elseif ($fromStatus === 'submitted') {
                        $updateData['triaged_by'] = $actor->id;
                        $updateData['triaged_at'] = now();
                        $auditNote = empty($reason) ? 'Laporan mulai ditinjau (in_review) oleh staf.' : $reason;
                    } elseif ($fromStatus === 'needs_info') {
                        $auditNote = empty($reason) ? 'Tim memberikan tanggapan/revisi atas permintaan informasi panitia.' : $reason;
                    }
                    break;

                case 'needs_info':
                    if (empty($reason)) {
                        throw new InvalidArgumentException('Permintaan informasi tambahan (needs_info) wajib menyertakan pesan klarifikasi.');
                    }
                    // Simpan komentar publik permintaan informasi dari admin
                    ReportComment::create([
                        'report_id' => $lockedReport->id,
                        'user_id' => $actor->id,
                        'is_internal' => false,
                        'body' => $reason,
                    ]);
                    $auditNote = "Meminta informasi tambahan dari tim: {$reason}";
                    break;

                case 'valid':
                    // Wajib vector final CVSS
                    $vectorFinal = $params['cvss_vector_final'] ?? $lockedReport->cvss_vector_final ?? $lockedReport->cvss_vector_claimed;
                    if (empty($vectorFinal)) {
                        throw new InvalidArgumentException('Penetapan status valid wajib menyertakan vector CVSS final.');
                    }

                    // Hitung skor dan severity final di server dari vector
                    $cvssCalc = CvssCalculator::calculate($vectorFinal);
                    $scoreFinal = $cvssCalc['score'];
                    $severityFinal = $cvssCalc['severity'];

                    // Jika vector final berbeda dari yang diajukan peserta, wajib ada catatan alasan
                    if ($vectorFinal !== $lockedReport->cvss_vector_claimed && empty($reason)) {
                        throw new InvalidArgumentException('Perubahan CVSS final dari klaim peserta wajib disertai dengan catatan alasan.');
                    }

                    $qualityFactor = isset($params['quality_factor']) ? (float) $params['quality_factor'] : ($lockedReport->quality_factor ? (float) $lockedReport->quality_factor : 1.00);

                    // Kalkulasi poin otomatis via PointsCalculator
                    $pointsResult = $this->pointsCalculator->calculateValidPoints($lockedReport, $severityFinal, $qualityFactor);
                    $calculatedPoints = $pointsResult['points'];

                    $updateData['cvss_vector_final'] = $vectorFinal;
                    $updateData['cvss_score_final'] = $scoreFinal;
                    $updateData['severity_final'] = $severityFinal;
                    $updateData['quality_factor'] = $qualityFactor;
                    $updateData['closed_at'] = now();

                    // Perubahan poin manual jika diisi admin
                    if (isset($params['points_override']) && $params['points_override'] !== null) {
                        $overridePoints = (int) $params['points_override'];
                        $overrideNote = trim($params['points_override_note'] ?? '');
                        if (empty($overrideNote)) {
                            throw new InvalidArgumentException('Penyesuaian poin manual wajib menyertakan catatan alasan.');
                        }
                        $updateData['points_awarded'] = $overridePoints;
                        $updateData['points_override_note'] = $overrideNote;
                        $auditNote = "Ditetapkan VALID (Poin Manual: {$overridePoints}, Alasan: {$overrideNote}). Perhitungan Asli: [{$pointsResult['audit_note']}]";
                    } else {
                        $updateData['points_awarded'] = $calculatedPoints;
                        $auditNote = "Ditetapkan VALID. {$pointsResult['audit_note']}".($reason ? " | Catatan: {$reason}" : '');
                    }
                    break;

                case 'invalid':
                    if (empty($reason)) {
                        throw new InvalidArgumentException('Penolakan laporan (invalid) wajib disertai dengan catatan alasan.');
                    }
                    $updateData['points_awarded'] = 0;
                    $updateData['closed_at'] = now();
                    $auditNote = "Laporan dinyatakan TIDAK VALID (Invalid). Alasan: {$reason}";
                    break;

                case 'duplicate':
                    $duplicateOfId = $params['duplicate_of'] ?? null;
                    if (! $duplicateOfId || (int) $duplicateOfId === (int) $lockedReport->id) {
                        throw new InvalidArgumentException('Penetapan status duplikat wajib memilih laporan asli (duplicate_of) yang sah.');
                    }

                    $originalReport = Report::where('id', $duplicateOfId)->first();
                    if (! $originalReport) {
                        throw new InvalidArgumentException('Laporan asli yang dirujuk sebagai duplikat tidak ditemukan.');
                    }

                    $dupPoints = $this->pointsCalculator->calculateDuplicatePoints();

                    $updateData['duplicate_of'] = $originalReport->id;
                    $updateData['points_awarded'] = $dupPoints;
                    $updateData['closed_at'] = now();
                    $auditNote = "Laporan ditandai DUPLIKAT dari #{$originalReport->code} (Poin Duplikat: {$dupPoints}).".($reason ? " Catatan: {$reason}" : '');
                    break;

                case 'rewarded':
                    $auditNote = empty($reason) ? 'Hadiah/bounty atas laporan ini telah diserahkan kepada tim.' : $reason;
                    break;
            }

            $lockedReport->update($updateData);

            // 4. Catat Riwayat Status di report_status_histories
            ReportStatusHistory::create([
                'report_id' => $lockedReport->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'changed_by' => $actor->id,
                'note' => $auditNote,
                'created_at' => now(),
            ]);

            // 5. Kirim Notifikasi Email (Berisi HANYA kode laporan dan status baru, aman dari kebocoran)
            if (! $isTeamReply && $lockedReport->reporter) {
                try {
                    $lockedReport->reporter->notify(
                        new ReportStatusChangedNotification(
                            $lockedReport->code ?? (string) $lockedReport->id,
                            $toStatus,
                            $reason ?: null
                        )
                    );
                } catch (\Throwable $e) {
                    // Penanganan kegagalan notifikasi agar tidak menggagalkan transaksi DB utama
                }
            }

            return $lockedReport->fresh();
        });
    }
}
