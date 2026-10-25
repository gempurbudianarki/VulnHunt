<?php

namespace Tests\Feature;

use App\Filament\Resources\CompetitionSettingResource\Pages\ListCompetitionSettings;
use App\Models\CompetitionSetting;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\CompetitionSettings;
use App\Services\TeamJoinService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->settings = app(CompetitionSettings::class);
    $this->settings->ensureAllKeysSeeded();
});

function createAdmin(): User
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

function createTriager(): User
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

function createParticipant(): User
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

test('triager dan peserta biasa dilarang mengakses halaman pengaturan lomba di filament (403)', function () {
    $triager = createTriager();
    $participant = createParticipant();

    // Triager mencoba akses HTTP langsung
    $this->actingAs($triager)
        ->get('/admin/competition-settings')
        ->assertForbidden();

    // Peserta mencoba akses HTTP langsung
    $this->actingAs($participant)
        ->get('/admin/competition-settings')
        ->assertForbidden();
});

test('admin dengan 2fa aktif dapat mengakses halaman pengaturan lomba di filament (200)', function () {
    $admin = createAdmin();

    $this->actingAs($admin)
        ->get('/admin/competition-settings')
        ->assertSuccessful()
        ->assertSee('Pengaturan Lomba')
        ->assertSee('max_active_reports_per_team')
        ->assertSee('rules_version');
});

test('validasi pengaturan menggunakan single source competition settings dan menolak nilai tidak valid', function () {
    $admin = createAdmin();

    $settingRecord = CompetitionSetting::where('key', 'max_active_reports_per_team')->firstOrFail();

    // Nilai tidak valid (> 50) ditolak oleh validasi class CompetitionSettings
    Livewire::actingAs($admin)
        ->test(ListCompetitionSettings::class)
        ->callTableAction('edit', $settingRecord, [
            'value' => '99',
        ])
        ->assertNotified('Validasi Pengaturan Gagal');

    // Nilai di database dan cache tetap tidak berubah
    expect($this->settings->get('max_active_reports_per_team'))->toBe(5);

    // Nilai valid (10) berhasil disimpan
    Livewire::actingAs($admin)
        ->test(ListCompetitionSettings::class)
        ->callTableAction('edit', $settingRecord, [
            'value' => '10',
        ])
        ->assertNotified('Pengaturan Berhasil Disimpan');

    expect($this->settings->get('max_active_reports_per_team'))->toBe(10);
});

test('tombol reset per key mengembalikan nilai ke default dan langsung terbaca di cache', function () {
    $admin = createAdmin();

    // Ubah nilai terlebih dahulu
    $this->settings->set('max_active_reports_per_team', 25, $admin->id);
    expect($this->settings->get('max_active_reports_per_team'))->toBe(25);

    $settingRecord = CompetitionSetting::where('key', 'max_active_reports_per_team')->firstOrFail();

    // Panggil action reset
    Livewire::actingAs($admin)
        ->test(ListCompetitionSettings::class)
        ->callTableAction('reset', $settingRecord)
        ->assertNotified('Pengaturan Direset');

    // Cache langsung terbaca kembali ke default (5) tanpa TTL
    expect($this->settings->get('max_active_reports_per_team'))->toBe(5);

    // Perubahan dicatat di activity log
    $log = Activity::where('log_name', 'competition_settings')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['key'])->toBe('max_active_reports_per_team')
        ->and($log->properties['old'])->toBe(25)
        ->and($log->properties['new'])->toBe(5);
});

test('perubahan rules_version dan allowed_email_domains dicatat di activity log dengan old dan new values', function () {
    $admin = createAdmin();

    $rulesRecord = CompetitionSetting::where('key', 'rules_version')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ListCompetitionSettings::class)
        ->callTableAction('edit', $rulesRecord, [
            'value' => '2',
        ])
        ->assertNotified('Pengaturan Berhasil Disimpan');

    expect($this->settings->get('rules_version'))->toBe('2');

    $log = Activity::where('log_name', 'competition_settings')
        ->where('properties->key', 'rules_version')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['key'])->toBe('rules_version')
        ->and((string) $log->properties['old'])->toBe('1')
        ->and((string) $log->properties['new'])->toBe('2');
});

test('menurunkan max_team_size di bawah ukuran tim yang ada tidak mengeluarkan anggota dan menghalangi anggota baru', function () {
    // 1. Buat tim dengan 4 anggota (sesuai max_team_size awal = 4)
    $leader = createParticipant();
    $member1 = createParticipant();
    $member2 = createParticipant();
    $member3 = createParticipant();

    $team = Team::create([
        'name' => 'Tim Uji Batas',
        'leader_id' => $leader->id,
        'invite_code' => 'BATAS4',
        'status' => 'active',
    ]);

    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader', 'joined_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member1->id, 'role' => 'member', 'joined_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member2->id, 'role' => 'member', 'joined_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member3->id, 'role' => 'member', 'joined_at' => now()]);

    expect($team->members()->count())->toBe(4);

    // 2. Admin menurunkan max_team_size menjadi 2
    $this->settings->set('max_team_size', 2);
    expect($this->settings->get('max_team_size'))->toBe(2);

    // 3. Pastikan tidak ada anggota yang dikeluarkan
    expect($team->members()->count())->toBe(4);
    expect(TeamMember::where('team_id', $team->id)->pluck('user_id')->all())->toEqual([
        $leader->id,
        $member1->id,
        $member2->id,
        $member3->id,
    ]);

    // 4. Pastikan anggota baru ke-5 dihalangi bergabung
    $newCandidate = createParticipant();
    $joinService = app(TeamJoinService::class);

    expect(fn () => $joinService->join($newCandidate, 'BATAS4'))
        ->toThrow(ValidationException::class);
});
