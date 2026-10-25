<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Report;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Services\CompetitionSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->settings = app(CompetitionSettings::class);
    $this->settings->ensureAllKeysSeeded();
});

function reviewAdmin(): User
{
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
        'is_active' => true,
    ]);
    $admin->assignRole('admin');

    return $admin;
}

function reviewTriager(): User
{
    $triager = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
        'is_active' => true,
    ]);
    $triager->assignRole('triager');

    return $triager;
}

function reviewParticipant(): User
{
    $participant = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'rules_accepted_version' => '1',
        'is_active' => true,
    ]);
    $participant->assignRole('participant');

    return $participant;
}

test('real http: akses seluruh resource baru diuji sebagai peserta dan triager (semua 403)', function () {
    $triager = reviewTriager();
    $participant = reviewParticipant();

    $newAdminEndpoints = [
        '/admin/competition-settings',
        '/admin/announcements',
        '/admin/announcements/create',
        '/admin/activity-logs',
        '/admin/export/reports',
        '/admin/export/leaderboard',
    ];

    foreach ($newAdminEndpoints as $endpoint) {
        // 1. Uji akses sebagai Triager
        $triagerResponse = $this->actingAs($triager)->get($endpoint);
        expect($triagerResponse->status())
            ->toBe(403, "Triager seharusnya dilarang (403) mengakses endpoint {$endpoint}");

        // 2. Uji akses sebagai Peserta
        $participantResponse = $this->actingAs($participant)->get($endpoint);
        expect($participantResponse->status())
            ->toBe(403, "Peserta seharusnya dilarang (403) mengakses endpoint {$endpoint}");
    }
});

test('real http: parameter liar pada ekspor csv ditangani dengan aman tanpa 500 atau sql injection', function () {
    $admin = reviewAdmin();

    $wildQueries = [
        '?filter=\'; DROP TABLE users; --',
        '?type=malicious&status[]=1&status[]=2&format=../../../etc/passwd',
        '?sort=union+select+1,2,3&limit=-999999999',
        '?page=<script>alert(1)</script>&query="><svg/onload=alert(1)>',
        '?id=\' OR \'1\'=\'1\' --',
    ];

    foreach ($wildQueries as $query) {
        $this->actingAs($admin)
            ->get('/admin/export/reports' . $query)
            ->assertSuccessful();

        $this->actingAs($admin)
            ->get('/admin/export/leaderboard' . $query)
            ->assertSuccessful();
    }
});

test('real http: mode hidden dan hidden_until_end lewat url langsung tidak membocorkan peringkat', function () {
    $participant = reviewParticipant();
    $admin = reviewAdmin();

    // Buat data tim & laporan bernilai
    $team = Team::create(['name' => 'Tim Rahasia Peringkat 1', 'leader_id' => $participant->id, 'status' => 'active']);
    $scope = Scope::create(['name' => 'Target', 'target' => 'https://t.ubbg.ac.id', 'asset_type' => 'web']);
    $vuln = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS']);

    Report::create([
        'code' => 'VH-2026-9999',
        'team_id' => $team->id,
        'reported_by' => $participant->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $vuln->id,
        'title' => 'Bug Skor Tinggi',
        'endpoint' => '/app',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'severity_final' => 'critical',
        'description' => 'Desc',
        'steps_to_reproduce' => 'Steps',
        'impact' => 'Impact',
        'status' => 'rewarded',
        'points_awarded' => 100,
        'submitted_at' => now(),
        'closed_at' => now(),
    ]);

    // Skenario 1: Mode hidden mutlak
    $this->settings->set('leaderboard_mode', 'hidden');

    // Akses via URL HTML langsung
    $htmlRes = $this->actingAs($participant)->get(route('leaderboard.index'));
    $htmlRes->assertSuccessful();
    $htmlRes->assertSee('Papan Peringkat Sedang Dibekukan');
    $htmlRes->assertDontSee('Tim Rahasia Peringkat 1');
    $htmlRes->assertDontSee('100 Poin');

    // Akses via API JSON langsung (Harus 403 Forbidden dengan payload data kosong [])
    $jsonRes = $this->actingAs($participant)->getJson(route('leaderboard.index'));
    $jsonRes->assertForbidden();
    expect($jsonRes->json('data'))->toBeArray()->toBeEmpty();
    expect($jsonRes->json('is_visible'))->toBeFalse();

    // Skenario 2: Mode hidden_until_end dengan submission_close_at null (tidak pernah terbuka otomatis)
    $this->settings->set('leaderboard_mode', 'hidden_until_end');
    $this->settings->set('submission_close_at', null);

    $htmlRes2 = $this->actingAs($participant)->get(route('leaderboard.index'));
    $htmlRes2->assertSuccessful();
    $htmlRes2->assertSee('Papan Peringkat Sedang Dibekukan');
    $htmlRes2->assertDontSee('Tim Rahasia Peringkat 1');
    $htmlRes2->assertDontSee('100 Poin');

    $jsonRes2 = $this->actingAs($participant)->getJson(route('leaderboard.index'));
    $jsonRes2->assertForbidden();
    expect($jsonRes2->json('data'))->toBeArray()->toBeEmpty();

    // Skenario 3: Mode hidden_until_end tapi waktu batas sudah lewat
    $this->settings->set('submission_close_at', now()->subMinute()->format('Y-m-d H:i:s'));

    $htmlRes3 = $this->actingAs($participant)->get(route('leaderboard.index'));
    $htmlRes3->assertSuccessful();
    $htmlRes3->assertSee('Tim Rahasia Peringkat 1');
    $htmlRes3->assertSee('100 Poin');

    $jsonRes3 = $this->actingAs($participant)->getJson(route('leaderboard.index'));
    $jsonRes3->assertSuccessful();
    expect($jsonRes3->json('data'))->not->toBeEmpty();
    expect($jsonRes3->json('data.0.team_name'))->toBe('Tim Rahasia Peringkat 1');
});
