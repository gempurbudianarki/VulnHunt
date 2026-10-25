<?php

namespace Tests\Feature;

use App\Filament\Resources\NdaSubmissionResource\Pages\ListNdaSubmissions;
use App\Filament\Resources\ScopeResource\Pages\CreateScope;
use App\Filament\Resources\TeamResource\Pages\ListTeams;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\NdaSubmission;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\NdaStatusChangedNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');
});

function createAdminUser(): User
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

function createTriagerUser(): User
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

function createParticipantUser(): User
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

test('admin dapat mengakses seluruh halaman resource utama di filament', function () {
    $admin = createAdminUser();

    $this->actingAs($admin)
        ->get('/admin/nda-submissions')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/scopes')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/vulnerability-types')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get('/admin/teams')
        ->assertSuccessful();
});

test('triager dilarang mengakses halaman verifikasi nda, peserta, dan tim di filament (403)', function () {
    $triager = createTriagerUser();

    $this->actingAs($triager)
        ->get('/admin/nda-submissions')
        ->assertForbidden();

    $this->actingAs($triager)
        ->get('/admin/users')
        ->assertForbidden();

    $this->actingAs($triager)
        ->get('/admin/teams')
        ->assertForbidden();
});

test('admin dapat menyetujui pengajuan nda dan mengirimkan notifikasi email ke peserta', function () {
    Notification::fake();

    $admin = createAdminUser();
    $participant = createParticipantUser();

    $nda = NdaSubmission::create([
        'user_id' => $participant->id,
        'document_number' => 'NDA-2026-0001-TEST',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/test.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy'),
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(ListNdaSubmissions::class)
        ->callTableAction('approve', $nda)
        ->assertHasNoTableActionErrors();

    $nda->refresh();
    expect($nda->status)->toBe('approved')
        ->and($nda->reviewed_by)->toBe($admin->id)
        ->and($nda->reviewed_at)->not->toBeNull();

    Notification::assertSentTo(
        $participant,
        NdaStatusChangedNotification::class,
        function ($notification) use ($nda) {
            return $notification->documentNumber === $nda->document_number
                && $notification->status === 'approved';
        }
    );
});

test('admin dapat menolak pengajuan nda dengan alasan wajib dan mengirimkan notifikasi email', function () {
    Notification::fake();

    $admin = createAdminUser();
    $participant = createParticipantUser();

    $nda = NdaSubmission::create([
        'user_id' => $participant->id,
        'document_number' => 'NDA-2026-0002-TEST',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/test.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy'),
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test(ListNdaSubmissions::class)
        ->callTableAction('reject', $nda, [
            'reject_reason' => 'Tanda tangan basah tidak terbaca jelas pada halaman 2.',
        ])
        ->assertHasNoTableActionErrors();

    $nda->refresh();
    expect($nda->status)->toBe('rejected')
        ->and($nda->reviewed_by)->toBe($admin->id)
        ->and($nda->reject_reason)->toBe('Tanda tangan basah tidak terbaca jelas pada halaman 2.');

    Notification::assertSentTo(
        $participant,
        NdaStatusChangedNotification::class,
        function ($notification) use ($nda) {
            return $notification->documentNumber === $nda->document_number
                && $notification->status === 'rejected'
                && $notification->rejectReason === 'Tanda tangan basah tidak terbaca jelas pada halaman 2.';
        }
    );
});

test('admin mengunduh berkas nda dicatat di spatie activity log', function () {
    $admin = createAdminUser();
    $participant = createParticipantUser();

    Storage::disk('private')->put('nda/signed_test.pdf', 'dummy content');

    $nda = NdaSubmission::create([
        'user_id' => $participant->id,
        'document_number' => 'NDA-2026-0003-TEST',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/signed_test.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy content'),
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('verification.download', $nda->id));
    $response->assertSuccessful();

    $log = Activity::where('log_name', 'nda')
        ->where('description', 'admin_download_nda')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->causer_id)->toBe($admin->id)
        ->and($log->properties['nda_id'])->toBe($nda->id)
        ->and($log->properties['document_number'])->toBe($nda->document_number);
});

test('triager dilarang mengunduh berkas nda peserta lain (403)', function () {
    $triager = createTriagerUser();
    $participant = createParticipantUser();

    Storage::disk('private')->put('nda/signed_test.pdf', 'dummy content');

    $nda = NdaSubmission::create([
        'user_id' => $participant->id,
        'document_number' => 'NDA-2026-0004-TEST',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/signed_test.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy content'),
        'status' => 'pending',
        'submitted_at' => now(),
    ]);

    $this->actingAs($triager)
        ->get(route('verification.download', $nda->id))
        ->assertForbidden();
});

test('admin dapat melakukan crud scope dengan criticality weight dan notes', function () {
    $admin = createAdminUser();

    Livewire::actingAs($admin)
        ->test(CreateScope::class)
        ->fillForm([
            'name' => 'API Kampus Baru',
            'target' => 'https://api-v2.ubbg.ac.id',
            'asset_type' => 'api',
            'scope_status' => 'in_scope',
            'criticality_weight' => 1.80,
            'description' => 'Target backend baru',
            'notes' => 'Hanya endpoint mahasiswa',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $scope = Scope::where('name', 'API Kampus Baru')->first();
    expect($scope)->not->toBeNull()
        ->and((float) $scope->criticality_weight)->toBe(1.80)
        ->and($scope->notes)->toBe('Hanya endpoint mahasiswa');
});

test('admin dapat mengaktifkan dan menonaktifkan pengguna di panel filament', function () {
    $admin = createAdminUser();
    $participant = createParticipantUser();

    expect($participant->is_active)->toBeTrue();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callTableAction('toggleActive', $participant)
        ->assertHasNoTableActionErrors();

    $participant->refresh();
    expect($participant->is_active)->toBeFalse();
});

test('admin dapat mendiskualifikasi tim, override kuota, dan memindahkan ketua tim', function () {
    $admin = createAdminUser();
    $leader = createParticipantUser();
    $member = createParticipantUser();

    $team = Team::create([
        'name' => 'Tim Pengujian Admin',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    TeamMember::create([
        'team_id' => $team->id,
        'user_id' => $member->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    // 1. Diskualifikasi Tim
    Livewire::actingAs($admin)
        ->test(ListTeams::class)
        ->callTableAction('toggleDisqualify', $team)
        ->assertHasNoTableActionErrors();

    $team->refresh();
    expect($team->isDisqualified())->toBeTrue();

    // 2. Override Kuota Laporan
    Livewire::actingAs($admin)
        ->test(ListTeams::class)
        ->callTableAction('overrideQuota', $team, [
            'report_quota_override' => 10,
        ])
        ->assertHasNoTableActionErrors();

    $team->refresh();
    expect($team->report_quota_override)->toBe(10);

    // 3. Pindah Ketua Tim
    Livewire::actingAs($admin)
        ->test(ListTeams::class)
        ->callTableAction('reassignLeader', $team, [
            'new_leader_id' => $member->id,
        ])
        ->assertHasNoTableActionErrors();

    $team->refresh();
    expect($team->leader_id)->toBe($member->id);
    expect(TeamMember::where('team_id', $team->id)->where('user_id', $member->id)->value('role'))->toBe('leader');
    expect(TeamMember::where('team_id', $team->id)->where('user_id', $leader->id)->value('role'))->toBe('member');
});
