<?php

namespace Tests\Feature;

use App\Filament\Resources\ReportResource\Pages\ViewReport;
use App\Models\NdaSubmission;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportComment;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Services\ReportStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');
});

function setupParticipant(string $email = 'p@ubbg.ac.id'): array
{
    $user = User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
        'must_change_password' => false,
        'rules_accepted_version' => '1',
        'rules_accepted_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole('participant');

    $team = Team::create([
        'name' => 'Tim '.substr(md5($email), 0, 6),
        'leader_id' => $user->id,
        'status' => 'active',
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $user->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    NdaSubmission::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'document_number' => 'NDA-'.substr(md5($email), 0, 8),
        'template_version' => '1.0',
        'signed_file_path' => 'nda/test.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy'),
        'status' => 'approved',
        'submitted_at' => now(),
        'reviewed_at' => now(),
    ]);

    return [$user, $team];
}

test('peserta mengirim is_internal, status=valid, severity_final, points_awarded, atau user_id lewat form pembuatan laporan diabaikan oleh server', function () {
    [$user, $team] = setupParticipant('tamper@ubbg.ac.id');

    $scope = Scope::create([
        'name' => 'Target Tamper',
        'target' => 'https://tamper.ubbg.ac.id',
        'scope_status' => 'in_scope',
        'is_active' => true,
    ]);

    $cwe = VulnerabilityType::create([
        'cwe_id' => 'CWE-89',
        'name' => 'SQL Injection',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post(route('reports.store'), [
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Temuan Celah Injeksi SQL',
        'endpoint' => '/search',
        'parameter' => 'q',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Deskripsi celah',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi terperinci untuk membuktikan celah keamanan. ', 5),
        'impact' => 'Dampak kebocoran database',
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',

        // INJEKSI PARAMETER ILEGAL DARI USER:
        'status' => 'valid',
        'severity_final' => 'critical',
        'points_awarded' => 9999,
        'user_id' => 9999,
        'reported_by' => 9999,
        'is_internal' => 1,
    ]);

    $response->assertSessionHasNoErrors();

    $report = Report::where('team_id', $team->id)->first();
    expect($report)->not->toBeNull()
        ->and($report->status)->toBe('submitted') // Tetap submitted!
        ->and($report->severity_final)->toBeNull() // Tetap null!
        ->and($report->points_awarded)->toBe(0) // Tetap 0!
        ->and($report->reported_by)->toBe($user->id); // Tetap id pemanggil asli!
});

test('peserta mengirim is_internal=1 atau manipulasi status saat membalas laporan diabaikan secara mutlak', function () {
    [$user, $team] = setupParticipant('reply_tamper@ubbg.ac.id');

    $scope = Scope::create(['name' => 'Target Rep', 'target' => 'https://rep.ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $report = Report::create([
        'code' => 'BB-2026-TAMP1',
        'team_id' => $team->id,
        'reported_by' => $user->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Butuh Info',
        'endpoint' => '/xss',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi. ', 10),
        'impact' => 'Dampak',
        'status' => 'needs_info', // Sedang needs_info
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('reports.reply', $report->code), [
        'reply_message' => 'Ini bukti video dan langkah tambahan dari tim.',
        // INJEKSI PARAMETER ILEGAL:
        'is_internal' => 1,
        'status' => 'valid',
        'points_awarded' => 500,
        'user_id' => 9999,
    ]);

    $response->assertRedirect(route('reports.show', $report->code));

    // Komentar yang tersimpan HARUS is_internal = false dan user_id = $user->id
    $comment = ReportComment::where('report_id', $report->id)->latest('id')->first();
    expect($comment)->not->toBeNull()
        ->and($comment->is_internal)->toBeFalse()
        ->and($comment->user_id)->toBe($user->id);

    // Status laporan kembali ke in_review (bukan valid), dan poin tetap 0
    $report->refresh();
    expect($report->status)->toBe('in_review')
        ->and($report->points_awarded)->toBe(0);
});

test('peserta membalas laporan yang statusnya bukan needs_info ditolak oleh server', function () {
    [$user, $team] = setupParticipant('non_needs_info@ubbg.ac.id');

    $scope = Scope::create(['name' => 'Target N', 'target' => 'https://n.ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $report = Report::create([
        'code' => 'BB-2026-INREV1',
        'team_id' => $team->id,
        'reported_by' => $user->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Dalam Review',
        'endpoint' => '/in-review',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi. ', 10),
        'impact' => 'Dampak',
        'status' => 'in_review', // Status bukan needs_info!
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($user)->post(route('reports.reply', $report->code), [
        'reply_message' => 'Mencoba membalas saat in_review...',
    ]);

    // Diarahkan kembali dengan pesan error
    $response->assertSessionHas('error', 'Laporan ini tidak sedang membutuhkan informasi tambahan (status bukan needs_info).');

    // Status laporan tidak berubah dan tidak ada komentar baru
    $report->refresh();
    expect($report->status)->toBe('in_review');
    expect(ReportComment::where('report_id', $report->id)->count())->toBe(0);
});

test('peserta menembak url admin langsung mendapatkan respons 403 atau redirect penolakan', function () {
    [$user] = setupParticipant('peserta_admin@ubbg.ac.id');

    // Peserta mencoba membuka dashboard admin filament
    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();

    // Peserta mencoba membuka antrean triage laporan admin
    $this->actingAs($user)
        ->get('/admin/reports')
        ->assertForbidden();

    // Peserta mencoba membuka verifikasi NDA
    $this->actingAs($user)
        ->get('/admin/nda-submissions')
        ->assertForbidden();
});

test('triager menembak aksi nda dan manipulasi tim lewat http mendapatkan 403', function () {
    $triager = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
        'is_active' => true,
    ]);
    $triager->assignRole('triager');

    [$participant] = setupParticipant('other_nda@ubbg.ac.id');

    // Triager mencoba membuka panel nda
    $this->actingAs($triager)
        ->get('/admin/nda-submissions')
        ->assertForbidden();

    // Triager mencoba download NDA milik peserta lain
    $nda = NdaSubmission::create([
        'user_id' => $participant->id,
        'document_number' => 'NDA-2026-FORBIDDEN',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/secret.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy'),
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $this->actingAs($triager)
        ->get(route('verification.download', $nda->id))
        ->assertForbidden();
});

test('peserta dilarang membaca atau mengunduh lampiran laporan milik tim lain (idor protection)', function () {
    [$userA, $teamA] = setupParticipant('tim_a@ubbg.ac.id');
    [$userB, $teamB] = setupParticipant('tim_b@ubbg.ac.id');

    $scope = Scope::create(['name' => 'Target IDOR', 'target' => 'https://idor.ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-89', 'name' => 'SQLi', 'is_active' => true]);

    $reportA = Report::create([
        'code' => 'BB-2026-TEAMA1',
        'team_id' => $teamA->id,
        'reported_by' => $userA->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Rahasia Tim A',
        'endpoint' => '/secret-a',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi Rahasia',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi. ', 10),
        'impact' => 'Dampak',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    Storage::disk('private')->put('reports/poc_team_a.png', 'fake image content');

    $attachmentA = ReportAttachment::create([
        'report_id' => $reportA->id,
        'uploaded_by' => $userA->id,
        'original_name' => 'poc_team_a.png',
        'stored_name' => 'poc_team_a.png',
        'disk_path' => 'reports/poc_team_a.png',
        'size' => 1024,
        'mime' => 'image/png',
        'sha256' => hash('sha256', 'fake image content'),
    ]);

    // 1. Peserta Tim B mencoba membuka detail laporan Tim A (404 Not Found mencegah enumerasi ID tim lain)
    $this->actingAs($userB)
        ->get(route('reports.show', $reportA->code))
        ->assertNotFound();

    // 2. Peserta Tim B mencoba mengunduh lampiran PoC Tim A (403 Forbidden)
    $this->actingAs($userB)
        ->get(route('reports.attachment.download', ['code' => $reportA->code, 'attachmentId' => $attachmentA->id]))
        ->assertForbidden();
});

test('transisi status tidak sah lewat request langsung ke action filament ditolak secara rapi', function () {
    $triager = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
        'is_active' => true,
    ]);
    $triager->assignRole('triager');

    $reporter = User::factory()->create(['email_verified_at' => now(), 'rules_accepted_version' => '1']);
    $reporter->assignRole('participant');

    $team = Team::create(['name' => 'Tim Fila Illegal', 'leader_id' => $reporter->id, 'status' => 'active']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $reporter->id, 'role' => 'leader', 'joined_at' => now()]);
    $scope = Scope::create(['name' => 'Target FI', 'target' => 'https://fi.ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-89', 'name' => 'SQLi', 'is_active' => true]);

    $report = Report::create([
        'code' => 'BB-2026-FTRANS1',
        'team_id' => $team->id,
        'reported_by' => $reporter->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Submitted Masih Baru',
        'endpoint' => '/illegal-transition',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi. ', 10),
        'impact' => 'Dampak',
        'status' => 'submitted', // Masih submitted!
        'submitted_at' => now(),
    ]);

    // 1. Pada level UI Livewire, action mark_valid dan mark_invalid HARUS HIDDEN saat status submitted
    Livewire::actingAs($triager)
        ->test(ViewReport::class, ['record' => $report->id])
        ->assertActionHidden('mark_valid')
        ->assertActionHidden('mark_invalid')
        ->assertActionVisible('start_review');

    // 2. Jika ada pihak yang memanggil transisi ilegal langsung ke service, sistem melempar InvalidArgumentException
    expect(fn () => app(ReportStateMachine::class)->transition($report, 'valid', $triager, [
        'cvss_vector_final' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
    ]))->toThrow(\InvalidArgumentException::class, "Transisi status laporan dari 'submitted' ke 'valid' tidak sah");

    // Status laporan tetap submitted dan tidak tembus menjadi valid
    $report->refresh();
    expect($report->status)->toBe('submitted');
});
