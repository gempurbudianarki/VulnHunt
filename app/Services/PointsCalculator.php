<?php

namespace App\Services;

use App\Models\Report;
use InvalidArgumentException;

class PointsCalculator
{
    public function __construct(
        protected CompetitionSettings $settings
    ) {}

    /**
     * Hitung poin untuk laporan berstatus 'valid' sesuai rumus Spesifikasi Bagian 8.4:
     * poin = round( base(severity_final) * scope.criticality_weight * quality_factor * diminishing )
     *
     * @return array{
     *     points: int,
     *     base: int,
     *     criticality_weight: float,
     *     quality_factor: float,
     *     diminishing: float,
     *     diminishing_enabled: bool,
     *     valid_sequence: int,
     *     audit_note: string
     * }
     */
    public function calculateValidPoints(
        Report $report,
        string $severityFinal,
        float $qualityFactor = 1.00
    ): array {
        if ($qualityFactor < 0.50 || $qualityFactor > 1.20) {
            throw new InvalidArgumentException('Faktor kualitas (quality_factor) harus berada di rentang 0.50 sampai 1.20.');
        }

        $pointsPerSeverity = $this->settings->get('points_per_severity', [
            'critical' => 100,
            'high' => 60,
            'medium' => 30,
            'low' => 10,
            'info' => 2,
        ]);

        $base = (int) ($pointsPerSeverity[$severityFinal] ?? 0);

        // Bobot kritis aset dari scope (rentang 1.00 sampai 2.00, default 1.00)
        $scope = $report->scope;
        $criticalityWeight = $scope ? (float) $scope->criticality_weight : 1.00;
        if ($criticalityWeight < 1.00) {
            $criticalityWeight = 1.00;
        }

        // Diminishing returns untuk temuan sejenis
        $diminishingEnabled = (bool) $this->settings->get('diminishing_enabled', false);
        $diminishing = 1.0;
        $validSequence = 1;

        if ($diminishingEnabled) {
            $steps = (array) $this->settings->get('diminishing_steps', [1.0, 0.5, 0.25]);

            // Hitung laporan valid ke-n dari tim yang sama dengan scope dan CWE yang sama
            // Urutan n ditentukan oleh submitted_at
            $previousValidCount = Report::where('team_id', $report->team_id)
                ->where('scope_id', $report->scope_id)
                ->where('vulnerability_type_id', $report->vulnerability_type_id)
                ->whereIn('status', ['valid', 'rewarded'])
                ->where('id', '!=', $report->id)
                ->where('submitted_at', '<=', $report->submitted_at)
                ->count();

            $validSequence = $previousValidCount + 1;
            $stepIndex = min($validSequence - 1, count($steps) - 1);
            $diminishing = (float) ($steps[$stepIndex] ?? 1.0);
        }

        $finalPoints = (int) round($base * $criticalityWeight * $qualityFactor * $diminishing);

        $auditNote = sprintf(
            'Kalkulasi Poin Otomatis: Base (%s=%d) * Bobot Aset (%.2f) * Faktor Kualitas (%.2f) * Diminishing (%.2f, temuan ke-%d) = %d Poin',
            $severityFinal,
            $base,
            $criticalityWeight,
            $qualityFactor,
            $diminishing,
            $validSequence,
            $finalPoints
        );

        return [
            'points' => $finalPoints,
            'base' => $base,
            'criticality_weight' => $criticalityWeight,
            'quality_factor' => $qualityFactor,
            'diminishing' => $diminishing,
            'diminishing_enabled' => $diminishingEnabled,
            'valid_sequence' => $validSequence,
            'audit_note' => $auditNote,
        ];
    }

    /**
     * Hitung poin untuk laporan duplikat sesuai spesifikasi: duplicate_points (default 0)
     */
    public function calculateDuplicatePoints(): int
    {
        return (int) $this->settings->get('duplicate_points', 0);
    }
}
