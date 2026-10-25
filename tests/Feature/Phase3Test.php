<?php

namespace Tests\Feature;

use App\Models\CompetitionSetting;
use App\Models\NdaSubmission;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportComment;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Notifications\ReportStatusChangedNotification;
use App\Services\CompetitionSettings;
use App\Services\PointsCalculator;
use App\Services\ReportStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');
});

/*
|--------------------------------------------------------------------------
| Helper Pembuatan Data Uji Fase 3
|--------------------------------------------------------------------------
*/
function createTestStaff(string $role = 'admin'): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

function createTestReport(string $status = 'submitted', ?Team $team = null, ?User $reporter = null): array
{
    if (! $reporter) {
        $reporter = User::factory()->create([
            'email_verified_at' => now(),
            'rules_accepted_version' => '1',
            'rules_accepted_at' => now(),
            'is_active' => true,
        ]);
        $reporter->assignRole('participant');
    }

    if (! $team) {
        $team = Team::create([
            'name' => 'Tim Uji '.Str::random(5),
            'leader_id' => $reporter->id,
            'invite_code' => strtoupper(Str::random(8)),
            'status' => 'active',
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $reporter->id,
            'role' => 'leader',
            'joined_at' => now(),
        ]);

        NdaSubmission::create([
            'user_id' => $reporter->id,
            'team_id' => $team->id,
            'document_number' => 'NDA-2026-'.str_pad((string) $reporter->id, 4, '0', STR_PAD_LEFT).'-'.Str::random(4),
            'template_version' => '1.0',
            'signed_file_path' => 'nda/test.pdf',
            'signed_file_sha256' => hash('sha256', 'test'),
            'status' => 'approved',
            'submitted_at' => now(),
            'reviewed_at' => now(),
        ]);
    }

    $scope = Scope::firstOrCreate(
        ['target' => 'https://siakad.ubbg.ac.id'],
        [
            'name' => 'SIAKAD UBBG',
            'asset_type' => 'web',
            'scope_status' => 'in_scope',
            'criticality_weight' => 1.50,
            'is_active' => true,
        ]
    );

    $cwe = VulnerabilityType::firstOrCreate(
        ['cwe_id' => 'CWE-89'],
        ['name' => 'SQL Injection', 'category' => 'Injection', 'is_active' => true]
    );

    $report = Report::create([
        'team_id' => $team->id,
        'reported_by' => $reporter->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'SQL Injection pada Filter Semester',
        'endpoint' => '/api/krs/filter',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Eksploitasi parameter semester_id tanpa sanitasi.',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi SQL injection dijelaskan lengkap. ', 5),
        'impact' => 'Akses penuh ke seluruh basis data akademik.',
        'status' => $status,
        'submitted_at' => now(),
    ]);

    return [$report, $team, $reporter, $scope, $cwe];
}

/*
|--------------------------------------------------------------------------
| 1. State Machine: Transisi Sah dan Tidak Sah
|--------------------------------------------------------------------------
*/
test('state machine mengizinkan seluruh transisi sah', function () {
    $admin = createTestStaff('admin');
    $stateMachine = app(ReportStateMachine::class);

    [$report, $team, $reporter] = createTestReport('submitted');

    // 1. submitted -> in_review
    $report = $stateMachine->transition($report, 'in_review', $admin);
    expect($report->status)->toBe('in_review')
        ->and($report->triaged_by)->toBe($admin->id);

    // 2. in_review -> needs_info
    $report = $stateMachine->transition($report, 'needs_info', $admin, [
        'reason' => 'Mohon sertakan screenshot request/response Burp Suite.',
    ]);
    expect($report->status)->toBe('needs_info');

    // 3. needs_info -> in_review (oleh tim pelapor)
    $report = $stateMachine->transition($report, 'in_review', $reporter, [
        'is_team_reply' => true,
        'reason' => 'Berikut screenshot HTTP request dan response.',
    ]);
    expect($report->status)->toBe('in_review');

    // 4. in_review -> valid
    $report = $stateMachine->transition($report, 'valid', $admin, [
        'cvss_vector_final' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'quality_factor' => 1.00,
    ]);
    expect($report->status)->toBe('valid')
        ->and($report->points_awarded)->toBeGreaterThan(0)
        ->and($report->closed_at)->not->toBeNull();

    // 5. valid -> rewarded
    $report = $stateMachine->transition($report, 'rewarded', $admin);
    expect($report->status)->toBe('rewarded');
});

test('state machine menolak transisi tidak sah di level server', function (string $from, string $to) {
    $admin = createTestStaff('admin');
    $stateMachine = app(ReportStateMachine::class);

    [$report] = createTestReport($from);

    expect(fn () => $stateMachine->transition($report, $to, $admin, ['reason' => 'Uji transisi']))
        ->toThrow(InvalidArgumentException::class);
})->with([
    ['submitted', 'valid'],       // Tidak boleh langsung valid tanpa in_review
    ['submitted', 'needs_info'],  // Tidak boleh langsung needs_info tanpa in_review
    ['submitted', 'duplicate'],   // Tidak boleh langsung duplikat tanpa in_review
    ['needs_info', 'valid'],      // needs_info tidak boleh langsung valid tanpa review
    ['invalid', 'valid'],         // invalid tidak bisa langsung valid (harus reopen dulu)
    ['duplicate', 'valid'],       // duplikat tidak bisa langsung valid (harus reopen dulu)
]);

/*
|--------------------------------------------------------------------------
| 2. Hak Akses Tiap Peran (Admin, Triager, Peserta)
|--------------------------------------------------------------------------
*/
test('triager dapat melakukan triage dan komentar tetapi DILARANG verifikasi NDA atau ubah settings', function () {
    $triager = createTestStaff('triager');
    $admin = createTestStaff('admin');

    [$report] = createTestReport('submitted');
    $stateMachine = app(ReportStateMachine::class);

    // 1. Triager BISA mengubah status laporan (triage)
    $report = $stateMachine->transition($report, 'in_review', $triager);
    expect($report->status)->toBe('in_review');

    // 2. Triager BISA menambah komentar internal
    expect(Gate::forUser($triager)->allows('addInternalComment', $report))->toBeTrue();

    // 3. Triager DILARANG memverifikasi / menyetujui dokumen NDA
    $nda = NdaSubmission::create([
        'user_id' => $report->reported_by,
        'team_id' => $report->team_id,
        'document_number' => 'NDA-2026-0001-ABCD',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/test.pdf',
        'signed_file_sha256' => hash('sha256', 'test'),
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    expect(Gate::forUser($triager)->allows('viewAny', NdaSubmission::class))->toBeFalse()
        ->and(Gate::forUser($triager)->allows('update', $nda))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', $nda))->toBeTrue();

    // 4. Triager DILARANG mengubah pengaturan kompetisi
    $setting = CompetitionSetting::create([
        'key' => 'test_key',
        'value' => ['value' => 123],
    ]);

    expect(Gate::forUser($triager)->allows('viewAny', CompetitionSetting::class))->toBeFalse()
        ->and(Gate::forUser($triager)->allows('update', $setting))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', $setting))->toBeTrue();
});

test('peserta dilarang memicu transisi triage selain membalas needs_info', function () {
    [$report, $team, $reporter] = createTestReport('submitted');
    $stateMachine = app(ReportStateMachine::class);

    // Peserta mencoba menandai laporannya sendiri sebagai in_review -> Ditolak AuthorizationException
    expect(fn () => $stateMachine->transition($report, 'in_review', $reporter))
        ->toThrow(AuthorizationException::class);
});

/*
|--------------------------------------------------------------------------
| 3. Komentar Internal TIDAK PERNAH Bocor ke Peserta
|--------------------------------------------------------------------------
*/
test('komentar is_internal TIDAK PERNAH muncul di portal peserta', function () {
    $admin = createTestStaff('admin');
    [$report, $team, $reporter] = createTestReport('submitted');

    // Buat 1 komentar publik dan 1 komentar internal khusus staf
    ReportComment::create([
        'report_id' => $report->id,
        'user_id' => $admin->id,
        'body' => 'KOMENTAR_PUBLIK_UNTUK_TIM_98765',
        'is_internal' => false,
    ]);

    ReportComment::create([
        'report_id' => $report->id,
        'user_id' => $admin->id,
        'body' => 'KOMENTAR_INTERNAL_RAHASIA_TRIAGER_98765',
        'is_internal' => true,
    ]);

    // 1. Peserta membuka halaman detail laporan di portal
    $response = $this->actingAs($reporter)->get(route('reports.show', $report->code));
    $response->assertOk();

    // Peserta BISA melihat komentar publik
    $response->assertSee('KOMENTAR_PUBLIK_UNTUK_TIM_98765');
    // Peserta TIDAK BISA melihat komentar internal
    $response->assertDontSee('KOMENTAR_INTERNAL_RAHASIA_TRIAGER_98765');

    // 2. Admin membuka halaman yang sama -> Admin BISA melihat komentar internal
    $adminResponse = $this->actingAs($admin)->get(route('reports.show', $report->code));
    $adminResponse->assertSee('KOMENTAR_INTERNAL_RAHASIA_TRIAGER_98765');
});

test('email notifikasi hanya memuat kode laporan dan status, tidak pernah isi laporan atau komentar internal', function () {
    $notification = new ReportStatusChangedNotification('BB-2026-0001', 'in_review');

    $dummyUser = User::factory()->make(['name' => 'Fulan Cyber']);
    $mail = $notification->toMail($dummyUser);

    expect($mail->subject)->toContain('BB-2026-0001')
        ->and(implode(' ', $mail->introLines))->toContain('BB-2026-0001')
        ->and(implode(' ', $mail->introLines))->toContain('Dalam Peninjauan (In Review)')
        ->and(implode(' ', $mail->introLines))->not->toContain('description')
        ->and(implode(' ', $mail->introLines))->not->toContain('steps_to_reproduce');
});

/*
|--------------------------------------------------------------------------
| 4. Perhitungan Poin (Termasuk Diminishing Aktif dan Nonaktif)
|--------------------------------------------------------------------------
*/
test('perhitungan poin valid tanpa diminishing: round(base * criticality_weight * quality_factor)', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('diminishing_enabled', false);

    $calculator = app(PointsCalculator::class);

    [$report, $team, $reporter, $scope] = createTestReport('in_review');
    // Scope criticality_weight = 1.50
    // Severity: critical -> base = 100
    // Quality factor: 1.20
    // Rumus: round(100 * 1.50 * 1.20 * 1.0) = 180

    $result = $calculator->calculateValidPoints($report, 'critical', 1.20);
    expect($result['points'])->toBe(180)
        ->and($result['base'])->toBe(100)
        ->and($result['criticality_weight'])->toBe(1.50)
        ->and($result['quality_factor'])->toBe(1.20)
        ->and($result['diminishing'])->toBe(1.0);
});

test('perhitungan poin dengan diminishing returns aktif untuk temuan sejenis dari tim yang sama', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('diminishing_enabled', true);
    $settings->set('diminishing_steps', [1.0, 0.5, 0.25]);

    $calculator = app(PointsCalculator::class);

    // Tim Alpha Security mengirim 3 temuan sejenis pada Scope & CWE yang sama
    [$report1, $team, $reporter, $scope, $cwe] = createTestReport('submitted');
    $scope->update(['criticality_weight' => 1.00]); // Biar base 100 gampang dihitung

    // Temuan 1: step 0 -> multiplier 1.0 -> 100 poin
    $res1 = $calculator->calculateValidPoints($report1, 'critical', 1.00);
    expect($res1['points'])->toBe(100)
        ->and($res1['diminishing'])->toBe(1.0)
        ->and($res1['valid_sequence'])->toBe(1);

    // Simulasikan temuan 1 berstatus valid
    $report1->update(['status' => 'valid', 'points_awarded' => 100]);

    // Temuan 2 (tim yang sama, scope sama, CWE sama, waktu submit lebih baru)
    $report2 = Report::create([
        'team_id' => $team->id,
        'reported_by' => $reporter->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'SQLi Bagian Kedua',
        'endpoint' => '/api/krs/filter2',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'SQLi 2',
        'steps_to_reproduce' => str_repeat('Langkah valid. ', 15),
        'impact' => 'Dampak',
        'status' => 'in_review',
        'submitted_at' => now()->addMinute(),
    ]);

    // Temuan 2: step 1 -> multiplier 0.5 -> 50 poin
    $res2 = $calculator->calculateValidPoints($report2, 'critical', 1.00);
    expect($res2['points'])->toBe(50)
        ->and($res2['diminishing'])->toBe(0.5)
        ->and($res2['valid_sequence'])->toBe(2);

    $report2->update(['status' => 'valid', 'points_awarded' => 50]);

    // Temuan 3 (tim yang sama, scope sama, CWE sama)
    $report3 = Report::create([
        'team_id' => $team->id,
        'reported_by' => $reporter->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'SQLi Bagian Ketiga',
        'endpoint' => '/api/krs/filter3',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'SQLi 3',
        'steps_to_reproduce' => str_repeat('Langkah valid. ', 15),
        'impact' => 'Dampak',
        'status' => 'in_review',
        'submitted_at' => now()->addMinutes(2),
    ]);

    // Temuan 3: step 2 -> multiplier 0.25 -> 25 poin
    $res3 = $calculator->calculateValidPoints($report3, 'critical', 1.00);
    expect($res3['points'])->toBe(25)
        ->and($res3['diminishing'])->toBe(0.25)
        ->and($res3['valid_sequence'])->toBe(3);
});

/*
|--------------------------------------------------------------------------
| 5. Pembukaan Kembali Keputusan (Reopen Decision)
|--------------------------------------------------------------------------
*/
test('membuka kembali keputusan valid/invalid/duplicate me-reset poin dan wajib alasan', function () {
    $admin = createTestStaff('admin');
    $stateMachine = app(ReportStateMachine::class);

    [$report] = createTestReport('in_review');

    // 1. Ditetapkan VALID dengan 100 poin
    $report = $stateMachine->transition($report, 'valid', $admin, [
        'cvss_vector_final' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'quality_factor' => 1.00,
    ]);
    expect($report->status)->toBe('valid')
        ->and($report->points_awarded)->toBeGreaterThan(0)
        ->and($report->closed_at)->not->toBeNull();

    // 2. Reopen TANPA alasan -> Ditolak InvalidArgumentException
    expect(fn () => $stateMachine->transition($report, 'in_review', $admin, ['reason' => '']))
        ->toThrow(InvalidArgumentException::class);

    // 3. Reopen DENGAN alasan -> Status kembali ke in_review, poin direset ke 0, closed_at null
    $reopened = $stateMachine->transition($report, 'in_review', $admin, [
        'reason' => 'Perlu verifikasi ulang dampak terhadap lingkungan produksi.',
    ]);

    expect($reopened->status)->toBe('in_review')
        ->and($reopened->points_awarded)->toBe(0)
        ->and($reopened->closed_at)->toBeNull();

    // Catatan audit tersimpan di report_status_histories
    $lastHistory = $reopened->statusHistories()->latest('id')->first();
    expect($lastHistory->from_status)->toBe('valid')
        ->and($lastHistory->to_status)->toBe('in_review')
        ->and($lastHistory->note)->toContain('Perlu verifikasi ulang dampak');
});

/*
|--------------------------------------------------------------------------
| 6. Laporan Duplikat Memakai duplicate_points
|--------------------------------------------------------------------------
*/
test('laporan duplikat mendapatkan duplicate_points dari competition_settings dan merujuk duplicate_of', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('duplicate_points', 10); // Panitia memberi 10 poin untuk laporan duplikat

    $admin = createTestStaff('admin');
    $stateMachine = app(ReportStateMachine::class);

    [$originalReport] = createTestReport('submitted');
    [$duplicateReport] = createTestReport('in_review');

    // Admin menetapkan laporan 2 sebagai duplikat dari laporan 1
    $processed = $stateMachine->transition($duplicateReport, 'duplicate', $admin, [
        'duplicate_of' => $originalReport->id,
        'reason' => 'Endpoint dan root cause sama persis dengan laporan terdahulu.',
    ]);

    expect($processed->status)->toBe('duplicate')
        ->and($processed->duplicate_of)->toBe($originalReport->id)
        ->and($processed->points_awarded)->toBe(10);
});

/*
|--------------------------------------------------------------------------
| 7. Unduhan Lampiran oleh Admin Dicatat di Log Aktivitas
|--------------------------------------------------------------------------
*/
test('unduhan lampiran oleh staf admin dicatat di activity_log', function () {
    $admin = createTestStaff('admin');
    [$report, $team, $reporter] = createTestReport('submitted');

    $diskPath = 'reports/admin_audit_proof.pdf';
    Storage::disk('private')->put($diskPath, '%PDF-1.4 dummy content');

    $attachment = ReportAttachment::create([
        'report_id' => $report->id,
        'uploaded_by' => $reporter->id,
        'original_name' => 'AdminProof.pdf',
        'stored_name' => 'admin_audit_proof.pdf',
        'disk_path' => $diskPath,
        'size' => 1024,
        'mime' => 'application/pdf',
        'sha256' => hash('sha256', '%PDF-1.4 dummy content'),
    ]);

    $response = $this->actingAs($admin)->get(route('reports.attachment.download', [$report->code, $attachment->id]));
    $response->assertOk();

    // Pastikan tercatat di activity_log
    $log = Activity::where('description', 'admin_download_attachment')
        ->where('subject_id', $attachment->id)
        ->where('causer_id', $admin->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['report_code'])->toBe($report->code);
});

/*
|--------------------------------------------------------------------------
| 8. Perubahan CVSS Final Wajib Catatan Alasan & Dihitung di Server
|--------------------------------------------------------------------------
*/
test('perubahan vector CVSS final dari klaim peserta wajib disertai catatan alasan', function () {
    $admin = createTestStaff('admin');
    $stateMachine = app(ReportStateMachine::class);

    [$report] = createTestReport('in_review');
    // Claimed: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H (9.8 Critical)
    // Admin mengubah ke vector Medium: CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N (6.5 Medium)

    $newVector = 'CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N';

    // 1. Tanpa catatan alasan -> Gagal (InvalidArgumentException)
    expect(fn () => $stateMachine->transition($report, 'valid', $admin, [
        'cvss_vector_final' => $newVector,
        'reason' => '',
    ]))->toThrow(InvalidArgumentException::class);

    // 2. Dengan catatan alasan -> Sukses, skor dan severity final dihitung server dari vector
    $validReport = $stateMachine->transition($report, 'valid', $admin, [
        'cvss_vector_final' => $newVector,
        'reason' => 'Perlu hak akses pengguna biasa (PR:L) dan hanya berdampak pada Confidentiality.',
    ]);

    expect((float) $validReport->cvss_score_final)->toBe(6.5)
        ->and($validReport->severity_final)->toBe('medium');
});
