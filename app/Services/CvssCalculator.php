<?php

namespace App\Services;

use InvalidArgumentException;

class CvssCalculator
{
    /**
     * Bobot metrik resmi CVSS v3.1 Base Metrics
     */
    protected const WEIGHTS = [
        'AV' => [
            'N' => 0.85,
            'A' => 0.62,
            'L' => 0.55,
            'P' => 0.20,
        ],
        'AC' => [
            'L' => 0.77,
            'H' => 0.44,
        ],
        'PR' => [
            'U' => [ // Scope Unchanged
                'N' => 0.85,
                'L' => 0.62,
                'H' => 0.27,
            ],
            'C' => [ // Scope Changed
                'N' => 0.85,
                'L' => 0.68,
                'H' => 0.50,
            ],
        ],
        'UI' => [
            'N' => 0.85,
            'R' => 0.62,
        ],
        'C' => [
            'H' => 0.56,
            'L' => 0.22,
            'N' => 0.00,
        ],
        'I' => [
            'H' => 0.56,
            'L' => 0.22,
            'N' => 0.00,
        ],
        'A' => [
            'H' => 0.56,
            'L' => 0.22,
            'N' => 0.00,
        ],
    ];

    /**
     * Fungsi Roundup sesuai spesifikasi resmi FIRST CVSS v3.1:
     * round up to the next 0.1 decimal point.
     */
    public static function roundup(float $x): float
    {
        $i = (int) round($x * 100000);
        if ($i % 10000 === 0) {
            return $i / 100000.0;
        }

        return (floor($i / 10000) + 1) / 10.0;
    }

    /**
     * Parse and calculate CVSS v3.1 score and severity.
     *
     * @return array{score: float, severity: string, vector: string, metrics: array<string, string>}
     */
    public static function calculate(string $vector): array
    {
        $metrics = self::parseVector($vector);

        $av = self::WEIGHTS['AV'][$metrics['AV']];
        $ac = self::WEIGHTS['AC'][$metrics['AC']];
        $scope = $metrics['S'];
        $pr = self::WEIGHTS['PR'][$scope][$metrics['PR']];
        $ui = self::WEIGHTS['UI'][$metrics['UI']];

        $c = self::WEIGHTS['C'][$metrics['C']];
        $i = self::WEIGHTS['I'][$metrics['I']];
        $a = self::WEIGHTS['A'][$metrics['A']];

        // 1. ISS = 1 - (1 - C) * (1 - I) * (1 - A)
        $iss = 1.0 - ((1.0 - $c) * (1.0 - $i) * (1.0 - $a));

        // 2. Impact
        if ($scope === 'U') {
            $impact = 6.42 * $iss;
        } else {
            $impact = 7.52 * ($iss - 0.029) - 3.25 * pow(($iss - 0.02), 15);
        }

        // 3. Exploitability
        $exploitability = 8.22 * $av * $ac * $pr * $ui;

        // 4. Base Score
        if ($impact <= 0) {
            $baseScore = 0.0;
        } elseif ($scope === 'U') {
            $baseScore = self::roundup(min($impact + $exploitability, 10.0));
        } else {
            $baseScore = self::roundup(min(1.08 * ($impact + $exploitability), 10.0));
        }

        $severity = self::scoreToSeverity($baseScore);
        $normalizedVector = self::formatVector($metrics);

        return [
            'score' => $baseScore,
            'severity' => $severity,
            'vector' => $normalizedVector,
            'metrics' => $metrics,
        ];
    }

    /**
     * Map CVSS score to severity string.
     */
    public static function scoreToSeverity(float $score): string
    {
        if ($score <= 0.0) {
            return 'info';
        }
        if ($score < 4.0) {
            return 'low';
        }
        if ($score < 7.0) {
            return 'medium';
        }
        if ($score < 9.0) {
            return 'high';
        }

        return 'critical';
    }

    /**
     * Parse raw vector string into validated metrics dictionary.
     *
     * @return array<string, string>
     */
    public static function parseVector(string $rawVector): array
    {
        $rawVector = trim($rawVector);
        if (str_starts_with($rawVector, 'CVSS:3.1/')) {
            $rawVector = substr($rawVector, 9);
        } elseif (str_starts_with($rawVector, 'CVSS:3.0/')) {
            $rawVector = substr($rawVector, 9);
        }

        $parts = explode('/', $rawVector);
        $metrics = [];

        foreach ($parts as $part) {
            $pair = explode(':', trim($part));
            if (count($pair) === 2) {
                $metrics[strtoupper(trim($pair[0]))] = strtoupper(trim($pair[1]));
            }
        }

        $required = ['AV', 'AC', 'PR', 'UI', 'S', 'C', 'I', 'A'];
        foreach ($required as $req) {
            if (! isset($metrics[$req])) {
                throw new InvalidArgumentException("Komponen CVSS '{$req}' wajib disertakan dalam vector.");
            }
        }

        // Validate values
        if (! in_array($metrics['AV'], ['N', 'A', 'L', 'P'], true)) {
            throw new InvalidArgumentException("Nilai AV tidak valid: {$metrics['AV']}");
        }
        if (! in_array($metrics['AC'], ['L', 'H'], true)) {
            throw new InvalidArgumentException("Nilai AC tidak valid: {$metrics['AC']}");
        }
        if (! in_array($metrics['PR'], ['N', 'L', 'H'], true)) {
            throw new InvalidArgumentException("Nilai PR tidak valid: {$metrics['PR']}");
        }
        if (! in_array($metrics['UI'], ['N', 'R'], true)) {
            throw new InvalidArgumentException("Nilai UI tidak valid: {$metrics['UI']}");
        }
        if (! in_array($metrics['S'], ['U', 'C'], true)) {
            throw new InvalidArgumentException("Nilai S tidak valid: {$metrics['S']}");
        }
        if (! in_array($metrics['C'], ['N', 'L', 'H'], true)) {
            throw new InvalidArgumentException("Nilai C tidak valid: {$metrics['C']}");
        }
        if (! in_array($metrics['I'], ['N', 'L', 'H'], true)) {
            throw new InvalidArgumentException("Nilai I tidak valid: {$metrics['I']}");
        }
        if (! in_array($metrics['A'], ['N', 'L', 'H'], true)) {
            throw new InvalidArgumentException("Nilai A tidak valid: {$metrics['A']}");
        }

        return $metrics;
    }

    /**
     * Standardize canonical CVSS:3.1 vector string.
     */
    public static function formatVector(array $metrics): string
    {
        return sprintf(
            'CVSS:3.1/AV:%s/AC:%s/PR:%s/UI:%s/S:%s/C:%s/I:%s/A:%s',
            $metrics['AV'],
            $metrics['AC'],
            $metrics['PR'],
            $metrics['UI'],
            $metrics['S'],
            $metrics['C'],
            $metrics['I'],
            $metrics['A']
        );
    }
}
