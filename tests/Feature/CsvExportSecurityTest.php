<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createExportAdmin(): User
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

function createExportTriager(): User
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

function createExportParticipant(): User
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

test('triager dan peserta dilarang mengunduh ekspor csv laporan dan peringkat (403)', function () {
    $triager = createExportTriager();
    $participant = createExportParticipant();

    // Triager mencoba akses ekspor laporan dan peringkat
    $this->actingAs($triager)
        ->get('/admin/export/reports')
        ->assertForbidden();

    $this->actingAs($triager)
        ->get('/admin/export/leaderboard')
        ->assertForbidden();

    // Peserta mencoba akses ekspor laporan dan peringkat
    $this->actingAs($participant)
        ->get('/admin/export/reports')
        ->assertForbidden();

    $this->actingAs($participant)
        ->get('/admin/export/leaderboard')
        ->assertForbidden();
});

test('admin dapat mengunduh ekspor csv laporan dengan utf-8 bom, metadata tanpa isi laporan, dan tercatat di activity log', function () {
    $admin = createExportAdmin();
    $participant = createExportParticipant();

    $team = Team::create([
        'name' => 'Tim Whitehat',
        'leader_id' => $participant->id,
        'status' => 'active',
    ]);

    $scope = Scope::create([
        'name' => 'Portal Akademik',
        'target' => 'https://akademik.ubbg.ac.id',
        'asset_type' => 'web',
    ]);

    $vulnType = VulnerabilityType::create([
        'cwe_id' => 'CWE-89',
        'name' => 'SQL Injection',
    ]);

    $secretDescription = 'RAHASIA: Payload eksploitasi sqlmap -u https://target --dbs berhasil membaca data sensitif mahasiswa.';
    $secretSteps = '1. Buka URL target; 2. Masukkan parameter id=1 OR 1=1; 3. Database terbaca.';

    $report = Report::create([
        'code' => 'VH-2026-0001',
        'team_id' => $team->id,
        'reported_by' => $participant->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $vulnType->id,
        'title' => 'SQL Injection pada endpoint nilai',
        'endpoint' => '/api/v1/grades',
        'parameter' => 'student_id',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'severity_final' => 'critical',
        'description' => $secretDescription,
        'steps_to_reproduce' => $secretSteps,
        'impact' => 'Dampak kebocoran data sangat parah.',
        'remediation' => 'Gunakan prepared statement.',
        'status' => 'rewarded',
        'points_awarded' => 100,
        'submitted_at' => now()->subDay(),
        'closed_at' => now(),
    ]);

    // Admin mengunduh ekspor laporan via HTTP
    $response = $this->actingAs($admin)->get('/admin/export/reports');
    $response->assertSuccessful();

    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // Ambil konten streaming
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    // 1. Periksa UTF-8 BOM di awal berkas (\xEF\xBB\xBF)
    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue();

    // 2. Periksa kolom yang diizinkan ada
    expect($content)->toContain('VH-2026-0001')
        ->and($content)->toContain('Tim Whitehat')
        ->and($content)->toContain('Portal Akademik')
        ->and($content)->toContain('CWE-89')
        ->and($content)->toContain('critical')
        ->and($content)->toContain('rewarded')
        ->and($content)->toContain('100');

    // 3. Pastikan TANPA isi laporan dan lampiran (description, steps, impact, remediation)
    expect($content)->not->toContain($secretDescription)
        ->and($content)->not->toContain($secretSteps)
        ->and($content)->not->toContain('Dampak kebocoran data sangat parah.')
        ->and($content)->not->toContain('Gunakan prepared statement.');

    // 4. Catat di Spatie Activity Log (siapa, kapan, jenis, jumlah baris)
    $log = Activity::where('log_name', 'export')
        ->where('properties->type', 'reports')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($admin->id)
        ->and($log->properties['type'])->toBe('reports')
        ->and($log->properties['row_count'])->toBe(1);
});

test('mitigasi csv injection: sel berawalan =, +, -, @, tab, dan cr diberi awalan tanda kutip tunggal', function () {
    $admin = createExportAdmin();
    $participant = createExportParticipant();

    // Nama tim dengan payload CSV Injection berbahaya
    $maliciousTeamName = '=HYPERLINK("http://x","y")';

    $team = Team::create([
        'name' => $maliciousTeamName,
        'leader_id' => $participant->id,
        'status' => 'active',
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $participant->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $scope = Scope::create([
        'name' => '@SUM(1+1)',
        'target' => 'https://target.ubbg.ac.id',
        'asset_type' => 'web',
    ]);

    $vulnType = VulnerabilityType::create([
        'cwe_id' => '+CWE-79',
        'name' => 'Cross-Site Scripting',
    ]);

    $report = Report::create([
        'code' => '-VH-EVIL',
        'team_id' => $team->id,
        'reported_by' => $participant->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $vulnType->id,
        'title' => 'Judul Bug',
        'endpoint' => '/login',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:N/A:N',
        'cvss_score_claimed' => 7.5,
        'severity_claimed' => 'high',
        'severity_final' => 'high',
        'description' => 'Uji formula injection',
        'steps_to_reproduce' => 'Langkah',
        'impact' => 'Dampak',
        'status' => 'valid',
        'points_awarded' => 60,
        'submitted_at' => now(),
    ]);

    // Ekspor laporan
    $response = $this->actingAs($admin)->get('/admin/export/reports');
    $response->assertSuccessful();

    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    // Sel yang berawalan =, +, -, @ wajib memiliki prefix ' (dalam raw CSV quote di-escape menjadi "")
    expect($content)->toContain("'=HYPERLINK(\"\"http://x\"\",\"\"y\"\")");
    expect($content)->toContain("'@SUM(1+1)");
    expect($content)->toContain("'+CWE-79");
    expect($content)->toContain("'-VH-EVIL");

    // Verifikasi juga jika di-parse sebagai sel CSV standar
    $lines = explode("\n", trim($content));
    $parsedRow = str_getcsv($lines[1]);
    expect($parsedRow[0])->toBe("'-VH-EVIL")
        ->and($parsedRow[1])->toBe("'" . $maliciousTeamName)
        ->and($parsedRow[2])->toBe("'@SUM(1+1)")
        ->and($parsedRow[3])->toBe("'+CWE-79");

    // Ekspor leaderboard
    $lbResponse = $this->actingAs($admin)->get('/admin/export/leaderboard');
    $lbResponse->assertSuccessful();

    ob_start();
    $lbResponse->sendContent();
    $lbContent = ob_get_clean();

    expect($lbContent)->toContain("'=HYPERLINK(\"\"http://x\"\",\"\"y\"\")");
    $lbLines = explode("\n", trim($lbContent));
    $lbParsedRow = str_getcsv($lbLines[1]);
    expect($lbParsedRow[1])->toBe("'" . $maliciousTeamName);
});
