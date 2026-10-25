<?php

use App\Services\CompetitionSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('competition settings memuat nilai default sesuai spesifikasi', function () {
    $settings = app(CompetitionSettings::class);

    expect($settings->get('max_active_reports_per_team'))->toBe(5)
        ->and($settings->get('min_team_size'))->toBe(2)
        ->and($settings->get('max_team_size'))->toBe(4)
        ->and($settings->get('points_per_severity'))->toBe([
            'critical' => 100,
            'high' => 60,
            'medium' => 30,
            'low' => 10,
            'info' => 2,
        ])
        ->and($settings->get('duplicate_points'))->toBe(0)
        ->and($settings->get('rules_version'))->toBe('1')
        ->and($settings->get('diminishing_enabled'))->toBeFalse()
        ->and($settings->get('diminishing_steps'))->toBe([1.0, 0.5, 0.25]);
});

test('perubahan setting langsung terbaca melalui cache bersama', function () {
    $settings = app(CompetitionSettings::class);

    // Initial read
    expect($settings->get('max_active_reports_per_team'))->toBe(5);

    // Update value to 10
    $settings->set('max_active_reports_per_team', 10);

    // Must be readable immediately
    expect($settings->get('max_active_reports_per_team'))->toBe(10);

    // Verify cache has the updated value
    $all = Cache::get(CompetitionSettings::CACHE_KEY);
    expect($all['max_active_reports_per_team'])->toBe(10);
});

test('validasi batas laporan aktif menolak nilai di luar rentang 1-50', function () {
    $settings = app(CompetitionSettings::class);

    expect(fn () => $settings->set('max_active_reports_per_team', 0))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $settings->set('max_active_reports_per_team', 51))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $settings->set('max_active_reports_per_team', 'lima'))
        ->toThrow(InvalidArgumentException::class);
});

test('validasi ukuran tim menolak nilai di luar rentang 1-10', function () {
    $settings = app(CompetitionSettings::class);

    expect(fn () => $settings->set('min_team_size', 0))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $settings->set('max_team_size', 11))
        ->toThrow(InvalidArgumentException::class);
});

test('validasi poin menolak bobot negatif', function () {
    $settings = app(CompetitionSettings::class);

    expect(fn () => $settings->set('duplicate_points', -5))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => $settings->set('points_per_severity', ['critical' => -100]))
        ->toThrow(InvalidArgumentException::class);
});

test('validasi mode leaderboard menolak nilai selain visible, hidden, hidden_until_end', function () {
    $settings = app(CompetitionSettings::class);

    expect(fn () => $settings->set('leaderboard_mode', 'invalid_mode'))
        ->toThrow(InvalidArgumentException::class);
});
