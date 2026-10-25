<?php

use App\Models\NdaSubmission;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('triager');
    Role::findOrCreate('participant');
});

test('berkas privat tidak dapat diakses lewat url storage langsung (harus 404)', function () {
    // 1. Simpan berkas dummy di disk private
    Storage::disk('private')->put('reports/secret_poc.txt', 'CONFIDENTIAL_POC_CONTENT');

    // 2. Coba akses langsung via URL web publik
    $resDirectPrivate = $this->get('/storage/private/reports/secret_poc.txt');
    $resDirectPrivate->assertNotFound();

    $resDirectReports = $this->get('/storage/reports/secret_poc.txt');
    $resDirectReports->assertNotFound();

    $resTraversal = $this->get('/storage/../storage/app/private/reports/secret_poc.txt');
    $resTraversal->assertNotFound();
});

test('unduhan lampiran privat dilindungi autentikasi dan otorisasi tim (anti-IDOR)', function () {
    Storage::fake('private');
    Storage::disk('private')->put('reports/evidence.png', 'PNG_IMAGE_CONTENT');

    $scope = Scope::create([
        'name' => 'Target Test',
        'target' => 'https://test.ubbg.ac.id',
        'asset_type' => 'web',
        'scope_status' => 'in_scope',
        'is_active' => true,
    ]);
    $cwe = VulnerabilityType::create([
        'cwe_id' => 'CWE-89',
        'name' => 'SQL Injection',
        'is_active' => true,
    ]);

    $owner = User::factory()->create(['email' => 'owner@ubbg.ac.id', 'rules_accepted_version' => '1', 'email_verified_at' => now()]);
    $ownerTeam = Team::create(['name' => 'Tim Owner', 'leader_id' => $owner->id, 'invite_code' => 'OWNER001', 'status' => 'active']);
    TeamMember::create(['team_id' => $ownerTeam->id, 'user_id' => $owner->id, 'role' => 'leader', 'joined_at' => now()]);
    NdaSubmission::create([
        'user_id' => $owner->id,
        'team_id' => $ownerTeam->id,
        'document_number' => 'NDA-2026-0001-OWNR',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/owner.pdf',
        'signed_file_sha256' => hash('sha256', 'owner_nda'),
        'status' => 'approved',
    ]);

    $report = Report::create([
        'code' => 'BB-2026-9999',
        'team_id' => $ownerTeam->id,
        'reported_by' => $owner->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Temuan Rahasia',
        'endpoint' => '/api/test',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi valid. ', 10),
        'impact' => 'Dampak',
        'status' => 'submitted',
        'revision_no' => 1,
    ]);

    $attachment = ReportAttachment::create([
        'report_id' => $report->id,
        'uploaded_by' => $owner->id,
        'original_name' => 'evidence.png',
        'stored_name' => 'evidence.png',
        'mime' => 'image/png',
        'size' => 1234,
        'sha256' => hash('sha256', 'PNG_IMAGE_CONTENT'),
        'disk_path' => 'reports/evidence.png',
    ]);

    // 1. Tamu (Unauthenticated) -> Ditolak / diarahkan ke login
    $guestRes = $this->get(route('reports.attachment.download', ['code' => $report->code, 'attachmentId' => $attachment->id]));
    $guestRes->assertRedirect(route('login'));

    // 2. Peserta Tim Lain (IDOR Attempt) -> 403 Forbidden
    $otherUser = User::factory()->create(['email' => 'hacker@ubbg.ac.id', 'rules_accepted_version' => '1', 'email_verified_at' => now()]);
    $otherTeam = Team::create(['name' => 'Tim Hacker', 'leader_id' => $otherUser->id, 'invite_code' => 'HACKER01', 'status' => 'active']);
    TeamMember::create(['team_id' => $otherTeam->id, 'user_id' => $otherUser->id, 'role' => 'leader', 'joined_at' => now()]);
    NdaSubmission::create([
        'user_id' => $otherUser->id,
        'team_id' => $otherTeam->id,
        'document_number' => 'NDA-2026-0002-HACK',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/hacker.pdf',
        'signed_file_sha256' => hash('sha256', 'hacker_nda'),
        'status' => 'approved',
    ]);

    $idorRes = $this->actingAs($otherUser)->get(route('reports.attachment.download', ['code' => $report->code, 'attachmentId' => $attachment->id]));
    $idorRes->assertForbidden();

    // 3. Pemilik Sah (Owner) -> Berhasil mengunduh
    $ownerRes = $this->actingAs($owner)->get(route('reports.attachment.download', ['code' => $report->code, 'attachmentId' => $attachment->id]));
    $ownerRes->assertSuccessful();
    $ownerRes->assertHeader('Content-Disposition');

    // 4. Triager (Panitia) -> Berhasil mengunduh
    $triager = User::factory()->create([
        'email' => 'triager@ubbg.ac.id',
        'must_change_password' => false,
        'two_factor_secret' => encrypt('SECRET'),
        'two_factor_confirmed_at' => now(),
    ]);
    $triager->assignRole('triager');
    $triagerRes = $this->actingAs($triager)->get(route('reports.attachment.download', ['code' => $report->code, 'attachmentId' => $attachment->id]));
    $triagerRes->assertSuccessful();
});
