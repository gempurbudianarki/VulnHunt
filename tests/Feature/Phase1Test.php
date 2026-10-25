<?php

use App\Models\NdaSubmission;
use App\Models\Report;
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

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');
});

test('registrasi berhasil dengan email kampus ubbg dan password minimal 10 karakter', function () {
    $response = $this->post('/register', [
        'name' => 'Fajar Pratama',
        'nim' => '210102001',
        'email' => 'fajar@ubbg.ac.id',
        'phone' => '081234567890',
        'study_program' => 'Informatika',
        'cohort_year' => 2022,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/app');

    $user = User::where('email', 'fajar@ubbg.ac.id')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole('participant'))->toBeTrue()
        ->and($user->nim)->toBe('210102001');
});

test('registrasi ditolak jika email bukan domain resmi kampus ubbg', function () {
    $response = $this->post('/register', [
        'name' => 'Hacker Luar',
        'nim' => '999999999',
        'email' => 'attacker@gmail.com',
        'phone' => '081234567890',
        'study_program' => 'Informatika',
        'cohort_year' => 2022,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasErrors(['email']);
    expect(User::where('email', 'attacker@gmail.com')->exists())->toBeFalse();
});

test('aturan satu orang satu tim: pengguna tidak dapat membuat atau bergabung lebih dari satu tim', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    // 1. Buat tim A
    $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'Tim Alpha',
    ])->assertRedirect(route('teams.index'));

    $freshUser = $user->fresh();
    expect($freshUser->currentTeam()->name)->toBe('Tim Alpha')
        ->and($freshUser->teamMember->isLeader())->toBeTrue();

    // 2. Coba buat tim B saat sudah punya tim -> ditolak
    $response = $this->actingAs($user)->post(route('teams.store'), [
        'name' => 'Tim Beta',
    ]);
    $response->assertSessionHas('error');

    // 3. Buat tim lain oleh user kedua
    $user2 = User::factory()->create(['email_verified_at' => now()]);
    $teamC = Team::create([
        'name' => 'Tim Charlie',
        'leader_id' => $user2->id,
        'invite_code' => 'CHARLIE1',
    ]);
    TeamMember::create([
        'team_id' => $teamC->id,
        'user_id' => $user2->id,
        'role' => 'leader',
    ]);

    // 4. Coba gabung tim C saat masih di tim A -> ditolak
    $joinResponse = $this->actingAs($user)->post(route('teams.join'), [
        'invite_code' => 'CHARLIE1',
    ]);
    $joinResponse->assertSessionHas('error');
});

test('gabung tim ditolak jika tim sudah penuh sesuai max_team_size', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_team_size', 2);

    $leader = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::create([
        'name' => 'Tim Duo',
        'leader_id' => $leader->id,
        'invite_code' => 'DUOTEAM1',
    ]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader']);

    $member1 = User::factory()->create(['email_verified_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member1->id, 'role' => 'member']);

    // Tim sekarang memiliki 2 anggota (penuh)
    $member2 = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($member2)->post(route('teams.join'), [
        'invite_code' => 'DUOTEAM1',
    ]);

    $response->assertSessionHasErrors(['invite_code']);
    expect($member2->fresh()->teamMember)->toBeNull();
});

test('anggota bisa keluar tim jika tim belum memiliki laporan', function () {
    $leader = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::create(['name' => 'Tim Uji', 'leader_id' => $leader->id, 'invite_code' => 'UJITEAM1']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader']);

    $member = User::factory()->create(['email_verified_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member']);

    $response = $this->actingAs($member)->post(route('teams.leave'));
    $response->assertRedirect(route('teams.index'));
    expect($member->fresh()->teamMember)->toBeNull();
});

test('anggota tidak bisa keluar tim jika tim sudah memiliki laporan', function () {
    $leader = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::create(['name' => 'Tim Laporan', 'leader_id' => $leader->id, 'invite_code' => 'LAPORAN1']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader']);

    $member = User::factory()->create(['email_verified_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member']);

    $scope = Scope::create(['name' => 'Test', 'target' => 'https://test.com', 'scope_status' => 'in_scope']);
    $vuln = VulnerabilityType::create(['cwe_id' => 'CWE-79', 'name' => 'XSS']);

    Report::create([
        'code' => 'BB-2026-0001',
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $vuln->id,
        'title' => 'Celah XSS',
        'endpoint' => '/search',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N',
        'cvss_score_claimed' => 6.1,
        'severity_claimed' => 'medium',
        'description' => 'deskripsi',
        'steps_to_reproduce' => 'langkah',
        'impact' => 'dampak',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($member)->post(route('teams.leave'));
    $response->assertSessionHas('error');
    expect($member->fresh()->teamMember)->not->toBeNull();
});

test('gerbang akses NDA berlaku per anggota: anggota approved bisa akses scope, anggota belum approved terkunci', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('rules_version', '1');

    $leader = User::factory()->create([
        'email_verified_at' => now(),
        'rules_accepted_version' => '1',
    ]);

    $member = User::factory()->create([
        'email_verified_at' => now(),
        'rules_accepted_version' => '1',
    ]);

    $team = Team::create(['name' => 'Cyber Squad', 'leader_id' => $leader->id, 'invite_code' => 'CYBERSQ1']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member']);

    // Leader punya NDA berstatus approved
    NdaSubmission::create([
        'user_id' => $leader->id,
        'team_id' => $team->id,
        'document_number' => 'NDA-2026-0001-ABCD',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/test.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy'),
        'status' => 'approved',
    ]);

    // Member belum punya NDA (atau masih pending)
    NdaSubmission::create([
        'user_id' => $member->id,
        'team_id' => $team->id,
        'document_number' => 'NDA-2026-0002-EFGH',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/member.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy2'),
        'status' => 'pending',
    ]);

    // Leader approved -> BISA mengakses /app/scope dan /app/laporan
    $this->actingAs($leader)->get(route('scope.index'))->assertOk();
    $this->actingAs($leader)->get(route('reports.index'))->assertOk();

    // Member pending -> TERKUNCI dan dialihkan ke /app/verifikasi
    $this->actingAs($member)->get(route('scope.index'))
        ->assertRedirect(route('verification.index'))
        ->assertSessionHas('error');

    $this->actingAs($member)->get(route('reports.index'))
        ->assertRedirect(route('verification.index'))
        ->assertSessionHas('error');
});

test('peserta yang sudah approved dilarang unggah ulang berkas yang dapat menjatuhkan statusnya', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'rules_accepted_version' => '1',
    ]);

    $team = Team::create(['name' => 'Tim Approved', 'leader_id' => $user->id, 'invite_code' => 'APPROVE1']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $user->id, 'role' => 'leader']);

    NdaSubmission::create([
        'user_id' => $user->id,
        'team_id' => $team->id,
        'document_number' => 'NDA-2026-0001-A1B2',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/approved.pdf',
        'signed_file_sha256' => hash('sha256', 'approved'),
        'status' => 'approved',
    ]);

    $file = UploadedFile::fake()->create('reupload.pdf', 500, 'application/pdf');

    $response = $this->actingAs($user)->post(route('verification.upload'), [
        'signed_file' => $file,
    ]);

    $response->assertSessionHas('error');
    // Status submission terbaru tetap approved
    expect($user->fresh()->latestNdaSubmission->status)->toBe('approved');
});

test('unduhan berkas NDA selalu mengembalikan Content-Disposition attachment dan nosniff', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $filePath = 'nda/valid_nda.pdf';
    Storage::disk('private')->put($filePath, 'konten pdf dummy');

    $submission = NdaSubmission::create([
        'user_id' => $user->id,
        'document_number' => 'NDA-2026-0001-9999',
        'template_version' => '1.0',
        'signed_file_path' => $filePath,
        'signed_file_sha256' => hash('sha256', 'konten pdf dummy'),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('verification.download', $submission->id));

    $response->assertOk();
    $response->assertHeader('Content-Disposition', 'attachment; filename="NDA-NDA-2026-0001-9999.pdf"');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
});

test('idor test: peserta lain tidak dapat mengunduh berkas NDA milik peserta berbeda', function () {
    $userA = User::factory()->create(['email_verified_at' => now()]);
    $userB = User::factory()->create(['email_verified_at' => now()]);

    $filePath = 'nda/userA_nda.pdf';
    Storage::disk('private')->put($filePath, 'konten user A');

    $submissionA = NdaSubmission::create([
        'user_id' => $userA->id,
        'document_number' => 'NDA-2026-0001-AAAA',
        'template_version' => '1.0',
        'signed_file_path' => $filePath,
        'signed_file_sha256' => hash('sha256', 'konten user A'),
        'status' => 'pending',
    ]);

    // User B mencoba mengunduh dokumen milik User A
    $response = $this->actingAs($userB)->get(route('verification.download', $submissionA->id));
    $response->assertForbidden();
});
