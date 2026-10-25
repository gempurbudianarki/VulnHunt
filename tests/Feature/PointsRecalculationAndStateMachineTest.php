<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Services\CompetitionSettings;
use App\Services\ReportStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');
});

test('diminishing returns menghitung ulang poin seluruh laporan valid saat validasi tidak berurutan dan saat reopen', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('diminishing_enabled', true);
    $settings->set('diminishing_steps', [1.0, 0.5, 0.25]);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('admin');

    $reporter = User::factory()->create([
        'email_verified_at' => now(),
        'rules_accepted_version' => '1',
    ]);
    $reporter->assignRole('participant');

    $team = Team::create([
        'name' => 'Tim Diminishing Test',
        'leader_id' => $reporter->id,
        'status' => 'active',
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $reporter->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $scope = Scope::create([
        'name' => 'Target Diminishing',
        'target' => 'https://diminishing.ubbg.ac.id',
        'scope_status' => 'in_scope',
        'criticality_weight' => 1.00,
        'is_active' => true,
    ]);

    $cwe = VulnerabilityType::create([
        'cwe_id' => 'CWE-79',
        'name' => 'XSS Stored',
        'is_active' => true,
    ]);

    // Laporan A dikirim jam 10:00
    $reportA = Report::create([
        'code' => 'BB-2026-A001',
        'team_id' => $team->id,
        'reported_by' => $reporter->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan A Dikirim Lebih Awal',
        'endpoint' => '/comment',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi A',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi A. ', 10),
        'impact' => 'Dampak A',
        'status' => 'in_review',
        'submitted_at' => now()->setTime(10, 0, 0),
    ]);

    // Laporan B dikirim jam 11:00
    $reportB = Report::create([
        'code' => 'BB-2026-B002',
        'team_id' => $team->id,
        'reported_by' => $reporter->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan B Dikirim Lebih Akhir',
        'endpoint' => '/profile',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi B',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi B. ', 10),
        'impact' => 'Dampak B',
        'status' => 'in_review',
        'submitted_at' => now()->setTime(11, 0, 0),
    ]);

    $stateMachine = app(ReportStateMachine::class);

    // 1. Admin memvalidasi Laporan B TERLEBIH DAHULU (validasi tidak berurutan)
    $stateMachine->transition($reportB, 'valid', $admin, [
        'cvss_vector_final' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'quality_factor' => 1.00,
    ]);

    $reportB->refresh();
    // Pada saat ini, Laporan B adalah satu-satunya laporan valid sehingga mendapat multiplier 1.0x (100 poin)
    expect($reportB->points_awarded)->toBe(100);

    // 2. Admin kemudian memvalidasi Laporan A yang dikirim LEBIH AWAL
    $stateMachine->transition($reportA, 'valid', $admin, [
        'cvss_vector_final' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'quality_factor' => 1.00,
    ]);

    $reportA->refresh();
    $reportB->refresh();

    // Laporan A (submitted 10:00) menjadi temuan ke-1 => multiplier 1.0x = 100 poin
    expect($reportA->points_awarded)->toBe(100);

    // Laporan B (submitted 11:00) otomatis dihitung ulang menjadi temuan ke-2 => multiplier 0.5x = 50 poin!
    expect($reportB->points_awarded)->toBe(50);

    // 3. Admin membuka kembali Laporan A (reopen keputusan)
    $stateMachine->transition($reportA, 'in_review', $admin, [
        'reason' => 'Perlu peninjauan ulang dampak keamanan.',
    ]);

    $reportA->refresh();
    $reportB->refresh();

    // Laporan A poinnya direset menjadi 0 karena statusnya kembali ke in_review
    expect($reportA->status)->toBe('in_review')
        ->and($reportA->points_awarded)->toBe(0);

    // Laporan B otomatis dihitung ulang menjadi temuan valid pertama tim => multiplier 1.0x = 100 poin!
    expect($reportB->status)->toBe('valid')
        ->and($reportB->points_awarded)->toBe(100);
});

test('pembukaan kembali laporan berstatus rewarded hanya boleh role admin dan ditolak untuk triager', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('admin');

    $triager = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $triager->assignRole('triager');

    $reporter = User::factory()->create(['email_verified_at' => now(), 'rules_accepted_version' => '1']);
    $reporter->assignRole('participant');

    $team = Team::create(['name' => 'Tim Rewarded Reopen', 'leader_id' => $reporter->id, 'status' => 'active']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $reporter->id, 'role' => 'leader', 'joined_at' => now()]);

    $scope = Scope::create(['name' => 'Target R', 'target' => 'https://r.ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-89', 'name' => 'SQLi', 'is_active' => true]);

    $report = Report::create([
        'code' => 'BB-2026-REW1',
        'team_id' => $team->id,
        'reported_by' => $reporter->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Berstatus Rewarded',
        'endpoint' => '/rewarded',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi. ', 10),
        'impact' => 'Dampak',
        'status' => 'rewarded',
        'points_awarded' => 100,
        'submitted_at' => now(),
    ]);

    $stateMachine = app(ReportStateMachine::class);

    // Triager mencoba reopen laporan berstatus rewarded -> HARUS DILEMPAR AuthorizationException
    expect(fn () => $stateMachine->transition($report, 'in_review', $triager, [
        'reason' => 'Triager mencoba membuka laporan rewarded',
    ]))->toThrow(AuthorizationException::class, 'Hanya admin yang memiliki wewenang untuk membuka kembali laporan yang telah berstatus rewarded.');

    // Admin membuka laporan berstatus rewarded -> BERHASIL
    $stateMachine->transition($report, 'in_review', $admin, [
        'reason' => 'Admin mengevaluasi ulang temuan setelah reward diserahkan.',
    ]);

    $report->refresh();
    expect($report->status)->toBe('in_review')
        ->and($report->points_awarded)->toBe(0);
});

test('transisi ilegal di state machine melempar InvalidArgumentException dengan pesan ramah', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('admin');

    $reporter = User::factory()->create(['email_verified_at' => now(), 'rules_accepted_version' => '1']);
    $reporter->assignRole('participant');

    $team = Team::create(['name' => 'Tim Illegal Transition', 'leader_id' => $reporter->id, 'status' => 'active']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $reporter->id, 'role' => 'leader', 'joined_at' => now()]);
    $scope = Scope::create(['name' => 'Target I', 'target' => 'https://i.ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $report = Report::create([
        'code' => 'BB-2026-ILL1',
        'team_id' => $team->id,
        'reported_by' => $reporter->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Submitted',
        'endpoint' => '/illegal',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi. ', 10),
        'impact' => 'Dampak',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $stateMachine = app(ReportStateMachine::class);

    // Langsung lompat dari submitted -> valid tanpa melalui in_review adalah ilegal
    expect(fn () => $stateMachine->transition($report, 'valid', $admin, [
        'cvss_vector_final' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
    ]))->toThrow(InvalidArgumentException::class, "Transisi status laporan dari 'submitted' ke 'valid' tidak sah dan ditolak oleh sistem.");
});
