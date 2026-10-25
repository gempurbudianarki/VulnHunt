<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function createLogAdmin(): User
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

function createLogTriager(): User
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

function createLogParticipant(): User
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

test('triager dan peserta dilarang mengakses audit log aktivitas di filament (403)', function () {
    $triager = createLogTriager();
    $participant = createLogParticipant();

    $this->actingAs($triager)
        ->get('/admin/activity-logs')
        ->assertForbidden();

    $this->actingAs($participant)
        ->get('/admin/activity-logs')
        ->assertForbidden();
});

test('admin dapat mengakses resource log aktivitas yang bersifat baca-saja tanpa aksi hapus', function () {
    $admin = createLogAdmin();

    // Buat contoh log aktivitas
    activity('auth')
        ->causedBy($admin)
        ->withProperties(['ip' => '127.0.0.1', 'action' => 'login_2fa_success'])
        ->log('Admin berhasil login dengan 2FA');

    $response = $this->actingAs($admin)->get('/admin/activity-logs');
    $response->assertSuccessful();

    $response->assertSee('Log Aktivitas');
    $response->assertSee('Admin berhasil login dengan 2FA');
    $response->assertSee('login_2fa_success');

    // Pastikan resource menolak aksi mutasi create, edit, dan delete
    expect(ActivityLogResource::canCreate())->toBeFalse()
        ->and(ActivityLogResource::canDeleteAny())->toBeFalse();

    $dummyLog = Activity::first();
    expect(ActivityLogResource::canDelete($dummyLog))->toBeFalse()
        ->and(ActivityLogResource::canEdit($dummyLog))->toBeFalse();
});

test('data sensitif (password, 2fa secret, token) disamarkan dan tidak bocor di tabel atau respons http', function () {
    $admin = createLogAdmin();

    $sensitiveSecret = 'SECRET_TOTP_KEY_JBSWY3DPEHPK3PXP';
    $sensitiveToken = 'SUPER_SECRET_BEARER_TOKEN_9999';
    $sensitivePassword = 'MySecretPassword123!';

    activity('auth')
        ->causedBy($admin)
        ->withProperties([
            'user_id' => $admin->id,
            'ip' => '192.168.1.100',
            'password' => $sensitivePassword,
            'two_factor_secret' => $sensitiveSecret,
            'api_token' => $sensitiveToken,
            'details' => [
                'recovery_codes' => ['CODE1', 'CODE2'],
                'safe_info' => 'Informasi Non Sensitif',
            ],
        ])
        ->log('Uji coba penyimpanan log kredensial');

    $log = Activity::latest('id')->first();

    // 1. Uji unit method sanitizeProperties
    $sanitized = ActivityLogResource::sanitizeProperties($log->properties->toArray());
    expect($sanitized['password'])->toBe('[TERLINDUNGI / REDACTED]')
        ->and($sanitized['two_factor_secret'])->toBe('[TERLINDUNGI / REDACTED]')
        ->and($sanitized['api_token'])->toBe('[TERLINDUNGI / REDACTED]')
        ->and($sanitized['details']['recovery_codes'])->toBe('[TERLINDUNGI / REDACTED]')
        ->and($sanitized['details']['safe_info'])->toBe('Informasi Non Sensitif');

    // 2. Uji HTTP request: admin membuka halaman log aktivitas
    $response = $this->actingAs($admin)->get('/admin/activity-logs');
    $response->assertSuccessful();

    // Data sensitif TIDAK BOLEH tampil di respons HTML
    $response->assertDontSee($sensitiveSecret);
    $response->assertDontSee($sensitiveToken);
    $response->assertDontSee($sensitivePassword);
    $response->assertDontSee('CODE1');

    // Label proteksi dan info aman harus tampil
    $response->assertSee('[TERLINDUNGI / REDACTED]');
    $response->assertSee('Informasi Non Sensitif');
});
