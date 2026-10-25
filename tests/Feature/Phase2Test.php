<?php

use App\Models\NdaSubmission;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Services\CompetitionSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');
});

/*
|--------------------------------------------------------------------------
| Helper untuk Menyiapkan Tim dan Anggota Berstatus Approved
|--------------------------------------------------------------------------
*/
function createApprovedTeamAndMember(string $teamName = 'Tim Elit Cyber'): array
{
    $settings = app(CompetitionSettings::class);
    $activeRulesVersion = (string) $settings->get('rules_version', '1');

    $leader = User::factory()->create([
        'email_verified_at' => now(),
        'rules_accepted_version' => $activeRulesVersion,
        'rules_accepted_at' => now(),
        'is_active' => true,
    ]);
    $leader->assignRole('participant');

    $team = Team::create([
        'name' => $teamName,
        'leader_id' => $leader->id,
        'invite_code' => strtoupper(Str::random(8)),
        'status' => 'active',
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    NdaSubmission::create([
        'user_id' => $leader->id,
        'team_id' => $team->id,
        'document_number' => NdaSubmission::generateDocumentNumber($leader->id),
        'template_version' => '1.0',
        'signed_file_path' => 'nda/test.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy'),
        'status' => 'approved',
        'submitted_at' => now(),
        'reviewed_at' => now(),
    ]);

    return [$team, $leader];
}

/*
|--------------------------------------------------------------------------
| 1. Validasi Server Ketat (Scope, CWE, Karakter, submitted_at)
|--------------------------------------------------------------------------
*/
test('pengiriman laporan menolak scope yang out_of_scope atau is_active false', function () {
    [$team, $leader] = createApprovedTeamAndMember();

    $validCwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $outOfScope = Scope::create([
        'name' => 'Payment Gateway',
        'target' => 'https://pay.ubbg.ac.id',
        'asset_type' => 'web',
        'scope_status' => 'out_of_scope',
        'is_active' => true,
    ]);

    $inactiveScope = Scope::create([
        'name' => 'Legacy System',
        'target' => 'https://old.ubbg.ac.id',
        'asset_type' => 'web',
        'scope_status' => 'in_scope',
        'is_active' => false,
    ]);

    $longSteps = str_repeat('Langkah reproduksi pengujian celah keamanan ini dijelaskan secara rinci. ', 4);

    // Coba kirim dengan target out_of_scope -> Ditolak
    $res1 = $this->actingAs($leader)->post(route('reports.store'), [
        'scope_id' => $outOfScope->id,
        'vulnerability_type_id' => $validCwe->id,
        'title' => 'Temuan di Out of Scope',
        'endpoint' => '/checkout',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Deskripsi temuan',
        'steps_to_reproduce' => $longSteps,
        'impact' => 'Dampak temuan',
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);
    $res1->assertSessionHasErrors(['scope_id']);

    // Coba kirim dengan target in_scope tetapi is_active = false -> Ditolak
    $res2 = $this->actingAs($leader)->post(route('reports.store'), [
        'scope_id' => $inactiveScope->id,
        'vulnerability_type_id' => $validCwe->id,
        'title' => 'Temuan di Inactive Scope',
        'endpoint' => '/old',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Deskripsi temuan',
        'steps_to_reproduce' => $longSteps,
        'impact' => 'Dampak temuan',
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);
    $res2->assertSessionHasErrors(['scope_id']);
});

test('pengiriman laporan menolak CWE yang is_active false', function () {
    [$team, $leader] = createApprovedTeamAndMember();

    $validScope = Scope::create([
        'name' => 'Portal Akademik',
        'target' => 'https://siakad.ubbg.ac.id',
        'asset_type' => 'web',
        'scope_status' => 'in_scope',
        'is_active' => true,
    ]);

    $inactiveCwe = VulnerabilityType::create([
        'cwe_id' => 'CWE-89',
        'name' => 'SQLi Inactive',
        'is_active' => false,
    ]);

    $longSteps = str_repeat('Langkah reproduksi pengujian celah keamanan ini dijelaskan secara rinci. ', 4);

    $response = $this->actingAs($leader)->post(route('reports.store'), [
        'scope_id' => $validScope->id,
        'vulnerability_type_id' => $inactiveCwe->id,
        'title' => 'SQL Injection pada Form Login',
        'endpoint' => '/login',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Deskripsi SQLi',
        'steps_to_reproduce' => $longSteps,
        'impact' => 'Dampak SQLi',
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);

    $response->assertSessionHasErrors(['vulnerability_type_id']);
});

test('langkah reproduksi (steps_to_reproduce) wajib minimal 100 karakter', function () {
    [$team, $leader] = createApprovedTeamAndMember();

    $validScope = Scope::create(['name' => 'Web Kampus', 'target' => 'https://ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $validCwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $shortSteps = 'Langkah terlalu pendek.'; // < 100 karakter

    $response = $this->actingAs($leader)->post(route('reports.store'), [
        'scope_id' => $validScope->id,
        'vulnerability_type_id' => $validCwe->id,
        'title' => 'Reflected XSS',
        'endpoint' => '/search',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => $shortSteps,
        'impact' => 'Dampak',
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);

    $response->assertSessionHasErrors(['steps_to_reproduce']);
});

test('submitted_at ditetapkan mutlak oleh server dan tidak bisa dimanipulasi lewat request', function () {
    [$team, $leader] = createApprovedTeamAndMember();

    $validScope = Scope::create(['name' => 'Web Kampus', 'target' => 'https://ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $validCwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $longSteps = str_repeat('Langkah reproduksi pengujian celah keamanan ini dijelaskan secara rinci. ', 4);
    $fakeDate = '2020-01-01 00:00:00';

    $response = $this->actingAs($leader)->post(route('reports.store'), [
        'scope_id' => $validScope->id,
        'vulnerability_type_id' => $validCwe->id,
        'title' => 'Manipulasi Tanggal Submit',
        'endpoint' => '/search',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => $longSteps,
        'impact' => 'Dampak',
        'submitted_at' => $fakeDate, // Injeksi nilai palsu
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);

    $report = Report::latest('id')->first();
    expect($report)->not->toBeNull();
    // submitted_at harus waktu saat ini, bukan 2020
    expect($report->submitted_at->year)->toBe((int) date('Y'))
        ->and($report->submitted_at->format('Y-m-d H:i:s'))->not->toBe($fakeDate);
});

/*
|--------------------------------------------------------------------------
| 2. File Upload Security & Pelarangan SVG
|--------------------------------------------------------------------------
*/
test('upload lampiran menolak format SVG secara mutlak', function () {
    [$team, $leader] = createApprovedTeamAndMember();

    $validScope = Scope::create(['name' => 'Web Kampus', 'target' => 'https://ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $validCwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);
    $longSteps = str_repeat('Langkah reproduksi pengujian celah keamanan ini dijelaskan secara rinci. ', 4);

    $svgFile = UploadedFile::fake()->createWithContent('exploit.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    $response = $this->actingAs($leader)->post(route('reports.store'), [
        'scope_id' => $validScope->id,
        'vulnerability_type_id' => $validCwe->id,
        'title' => 'XSS via SVG Payload',
        'endpoint' => '/upload',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => $longSteps,
        'impact' => 'Dampak',
        'attachments' => [$svgFile],
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);

    $response->assertSessionHasErrors(['attachments']);
    $errorMsg = session('errors')->first('attachments');
    expect($errorMsg)->toContain('SVG dilarang');
});

test('unduhan lampiran selalu mengembalikan Content-Disposition attachment dan nosniff', function () {
    [$team, $leader] = createApprovedTeamAndMember();

    $validScope = Scope::create(['name' => 'Web Kampus', 'target' => 'https://ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $validCwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $report = Report::create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $validScope->id,
        'vulnerability_type_id' => $validCwe->id,
        'title' => 'Laporan Unduhan Lampiran',
        'endpoint' => '/test',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah valid. ', 15),
        'impact' => 'Dampak',
        'status' => 'submitted',
    ]);

    $diskPath = 'reports/dummy_proof.pdf';
    Storage::disk('private')->put($diskPath, '%PDF-1.4 dummy content');

    $attachment = ReportAttachment::create([
        'report_id' => $report->id,
        'uploaded_by' => $leader->id,
        'original_name' => 'ProofOfConcept.pdf',
        'stored_name' => 'dummy_proof.pdf',
        'disk_path' => $diskPath,
        'size' => 2048,
        'mime' => 'application/pdf',
        'sha256' => hash('sha256', '%PDF-1.4 dummy content'),
    ]);

    $response = $this->actingAs($leader)->get(route('reports.attachment.download', [$report->code, $attachment->id]));

    $response->assertOk();
    $response->assertHeader('Content-Disposition', 'attachment; filename="ProofOfConcept.pdf"');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

/*
|--------------------------------------------------------------------------
| 3. Proteksi IDOR Antar Tim
|--------------------------------------------------------------------------
*/
test('idor test: Tim A tidak bisa melihat laporan atau mengunduh lampiran milik Tim B', function () {
    [$teamA, $memberA] = createApprovedTeamAndMember('Tim Alpha Security');
    [$teamB, $memberB] = createApprovedTeamAndMember('Tim Beta Security');

    $scope = Scope::create(['name' => 'Web Kampus', 'target' => 'https://ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $reportB = Report::create([
        'team_id' => $teamB->id,
        'reported_by' => $memberB->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Rahasia Temuan Tim B',
        'endpoint' => '/secret-b',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi rahasia B',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi tim B. ', 15),
        'impact' => 'Dampak tim B',
        'status' => 'submitted',
    ]);

    $diskPath = 'reports/secret_b.png';
    Storage::disk('private')->put($diskPath, 'fake-png-content');

    $attB = ReportAttachment::create([
        'report_id' => $reportB->id,
        'uploaded_by' => $memberB->id,
        'original_name' => 'Bukti_B.png',
        'stored_name' => 'secret_b.png',
        'disk_path' => $diskPath,
        'size' => 1024,
        'mime' => 'image/png',
        'sha256' => hash('sha256', 'fake-png-content'),
    ]);

    // 1. Member A mencoba melihat halaman detail laporan milik Tim B -> Ditolak (404/403)
    $viewResponse = $this->actingAs($memberA)->get(route('reports.show', $reportB->code));
    expect(in_array($viewResponse->status(), [403, 404]))->toBeTrue();

    // 2. Member A mencoba mengunduh lampiran milik Tim B -> Ditolak (403 Forbidden)
    $downloadResponse = $this->actingAs($memberA)->get(route('reports.attachment.download', [$reportB->code, $attB->id]));
    $downloadResponse->assertForbidden();

    // 3. Staf Admin BISA melihat dan mengunduh laporan & lampiran tim mana pun
    $admin = User::factory()->create(['email_verified_at' => now(), 'must_change_password' => false, 'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()]);
    $admin->assignRole('admin');

    $adminView = $this->actingAs($admin)->get(route('reports.show', $reportB->code));
    $adminView->assertOk();

    $adminDownload = $this->actingAs($admin)->get(route('reports.attachment.download', [$reportB->code, $attB->id]));
    $adminDownload->assertOk();
});

/*
|--------------------------------------------------------------------------
| 4. Invarian Opsi B (DECISIONS.md Poin 6 & Kuota Aktif)
|--------------------------------------------------------------------------
*/
test('tim dengan kuota laporan aktif penuh tetap BISA membalas laporan berstatus needs_info', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_active_reports_per_team', 2); // Kuota aktif maks 2

    [$team, $leader] = createApprovedTeamAndMember();

    $scope = Scope::create(['name' => 'Web Kampus', 'target' => 'https://ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    // Buat 2 laporan aktif (status submitted) -> kuota tim penuh (2/2)
    Report::create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Aktif 1',
        'endpoint' => '/act1',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi 1',
        'steps_to_reproduce' => str_repeat('Langkah 1. ', 15),
        'impact' => 'Dampak 1',
        'status' => 'submitted',
    ]);

    Report::create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Aktif 2',
        'endpoint' => '/act2',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi 2',
        'steps_to_reproduce' => str_repeat('Langkah 2. ', 15),
        'impact' => 'Dampak 2',
        'status' => 'in_review',
    ]);

    // Buat 1 laporan lain berstatus needs_info
    $needsInfoReport = Report::create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Perlu Info',
        'endpoint' => '/needs-info',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi needs info',
        'steps_to_reproduce' => str_repeat('Langkah awal. ', 15),
        'impact' => 'Dampak',
        'status' => 'needs_info',
    ]);

    // Tim saat ini memiliki 2 laporan aktif (submitted + in_review) dan 1 needs_info
    expect($team->activeReportsCount())->toBe(2);

    // INVARIAN 1: Kirim laporan baru HARUS DITOLAK karena kuota aktif penuh
    $newReportAttempt = $this->actingAs($leader)->post(route('reports.store'), [
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Baru Ditolak',
        'endpoint' => '/new-attempt',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah baru. ', 15),
        'impact' => 'Dampak',
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);
    $newReportAttempt->assertSessionHasErrors(['title']);

    // INVARIAN 2: Tim membalas laporan needs_info -> TETAP DIIZINKAN (exempt dari kuota)
    $replyResponse = $this->actingAs($leader)->post(route('reports.reply', $needsInfoReport->code), [
        'reply_message' => 'Berikut informasi tambahan yang diminta oleh admin.',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi yang telah direvisi dan diperjelas. ', 5),
    ]);

    $replyResponse->assertRedirect(route('reports.show', $needsInfoReport->code));
    $replyResponse->assertSessionHas('success');

    // Status berhasil bertransisi menjadi in_review
    $freshReport = $needsInfoReport->fresh();
    expect($freshReport->status)->toBe('in_review')
        ->and($freshReport->revision_no)->toBe(2);

    // Sekarang laporan aktif sementara menjadi 3 (2 + 1) sesuai invarian Opsi B
    expect($team->activeReportsCount())->toBe(3);
});

test('tim tidak bisa mengubah status laporannya sendiri menjadi needs_info', function () {
    [$team, $leader] = createApprovedTeamAndMember();

    $scope = Scope::create(['name' => 'Web Kampus', 'target' => 'https://ubbg.ac.id', 'scope_status' => 'in_scope', 'is_active' => true]);
    $cwe = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS', 'is_active' => true]);

    $report = Report::create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Submitted',
        'endpoint' => '/test',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi. ', 15),
        'impact' => 'Dampak',
        'status' => 'submitted',
    ]);

    // Tim mencoba memanggil reply pada laporan yang statusnya submitted (bukan needs_info)
    $response = $this->actingAs($leader)->post(route('reports.reply', $report->code), [
        'reply_message' => 'Coba ubah status sendiri',
    ]);

    $response->assertSessionHas('error');
    // Status tetap submitted, tidak berubah
    expect($report->fresh()->status)->toBe('submitted');
});
