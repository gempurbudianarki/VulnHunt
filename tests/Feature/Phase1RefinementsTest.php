<?php

use App\Models\NdaSubmission;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\CompetitionSettings;
use Database\Seeders\InitialAdminSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('private');
    RateLimiter::clear('login');
    RateLimiter::clear('register');
    RateLimiter::clear('join-team');
});

/*
|--------------------------------------------------------------------------
| 1. Panel Filament & 2FA TOTP Gate
|--------------------------------------------------------------------------
*/
test('peserta biasa mendapat 403 saat mencoba mengakses /admin', function () {
    $participant = User::factory()->create([
        'email_verified_at' => now(),
    ]);
    $participant->assignRole('participant');

    $response = $this->actingAs($participant)->get('/admin');
    $response->assertForbidden();
});

test('admin tanpa 2FA atau dengan must_change_password mendapat 403 di /admin', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => true,
    ]);
    $admin->assignRole('admin');

    // Belum pasang 2FA dan must_change_password = true
    $response = $this->actingAs($admin)->get('/admin');
    $response->assertForbidden();
});

test('admin dengan 2FA aktif dan password sudah diganti dapat mengakses /admin', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin');
    $response->assertOk();
});

/*
|--------------------------------------------------------------------------
| 2. Akun Admin Awal & Force Setup pada Login Pertama
|--------------------------------------------------------------------------
*/
test('InitialAdminSeeder melempar exception jika env credential kosong', function () {
    $origEmail = $_ENV['INITIAL_ADMIN_EMAIL'] ?? null;
    $origPassword = $_ENV['INITIAL_ADMIN_PASSWORD'] ?? null;

    unset($_ENV['INITIAL_ADMIN_EMAIL'], $_SERVER['INITIAL_ADMIN_EMAIL']);
    unset($_ENV['INITIAL_ADMIN_PASSWORD'], $_SERVER['INITIAL_ADMIN_PASSWORD']);
    putenv('INITIAL_ADMIN_EMAIL');
    putenv('INITIAL_ADMIN_PASSWORD');

    try {
        expect(fn () => (new InitialAdminSeeder)->run())
            ->toThrow(RuntimeException::class);
    } finally {
        if ($origEmail !== null) {
            $_ENV['INITIAL_ADMIN_EMAIL'] = $origEmail;
            $_SERVER['INITIAL_ADMIN_EMAIL'] = $origEmail;
            putenv("INITIAL_ADMIN_EMAIL={$origEmail}");
        }
        if ($origPassword !== null) {
            $_ENV['INITIAL_ADMIN_PASSWORD'] = $origPassword;
            $_SERVER['INITIAL_ADMIN_PASSWORD'] = $origPassword;
            putenv("INITIAL_ADMIN_PASSWORD={$origPassword}");
        }
    }
});

test('InitialAdminSeeder membuat admin dengan flag must_change_password true tanpa kredensial hardcode', function () {
    $_ENV['INITIAL_ADMIN_EMAIL'] = 'admin_test@ubbg.ac.id';
    $_SERVER['INITIAL_ADMIN_EMAIL'] = 'admin_test@ubbg.ac.id';
    putenv('INITIAL_ADMIN_EMAIL=admin_test@ubbg.ac.id');

    $_ENV['INITIAL_ADMIN_PASSWORD'] = 'RahasiaSekali123!';
    $_SERVER['INITIAL_ADMIN_PASSWORD'] = 'RahasiaSekali123!';
    putenv('INITIAL_ADMIN_PASSWORD=RahasiaSekali123!');

    (new InitialAdminSeeder)->run();

    $admin = User::where('email', 'admin_test@ubbg.ac.id')->first();
    expect($admin)->not->toBeNull()
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->must_change_password)->toBeTrue()
        ->and($admin->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('alur inisialisasi keamanan staf: wajib ganti password dan verifikasi kode TOTP 2FA', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'password' => bcrypt('PasswordLama123!'),
        'must_change_password' => true,
    ]);
    $admin->assignRole('admin');

    // Kunjungi halaman setup keamanan staf
    $response = $this->actingAs($admin)->get(route('security.setup'));
    $response->assertOk();
    $response->assertSee('Aktivasi Keamanan Akun Admin');

    // Fresh user untuk membaca two_factor_secret yang di-generate
    $freshAdmin = $admin->fresh();
    expect($freshAdmin->two_factor_secret)->not->toBeNull();

    $secret = decrypt($freshAdmin->two_factor_secret);

    // 1. Submit dengan kode TOTP salah -> ditolak
    $failResponse = $this->actingAs($admin)->post(route('security.setup.save'), [
        'current_password' => 'PasswordLama123!',
        'password' => 'PasswordBaruKuat2026!',
        'password_confirmation' => 'PasswordBaruKuat2026!',
        'code' => '000000',
    ]);
    $failResponse->assertSessionHasErrors(['code']);
    expect($admin->fresh()->must_change_password)->toBeTrue();

    // 2. Submit dengan kode TOTP valid
    $validCode = (new Google2FA)->getCurrentOtp($secret);

    $successResponse = $this->actingAs($admin)->post(route('security.setup.save'), [
        'current_password' => 'PasswordLama123!',
        'password' => 'PasswordBaruKuat2026!',
        'password_confirmation' => 'PasswordBaruKuat2026!',
        'code' => $validCode,
    ]);

    $successResponse->assertRedirect('/admin');
    $updatedAdmin = $admin->fresh();
    expect($updatedAdmin->must_change_password)->toBeFalse()
        ->and($updatedAdmin->hasEnabledTwoFactorAuthentication())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| 5. Domain Email di competition_settings
|--------------------------------------------------------------------------
*/
test('registrasi menerima subdomain resmi kampus (contoh mhs.ubbg.ac.id)', function () {
    $response = $this->post('/register', [
        'name' => 'Mahasiswa UBBG',
        'nim' => '210102099',
        'email' => 'mahasiswa@mhs.ubbg.ac.id',
        'phone' => '081234567890',
        'study_program' => 'Informatika',
        'cohort_year' => 2022,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/app');
    expect(User::where('email', 'mahasiswa@mhs.ubbg.ac.id')->exists())->toBeTrue();
});

test('jika daftar allowed_email_domains kosong, registrasi menerima semua domain', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('allowed_email_domains', []);

    $response = $this->post('/register', [
        'name' => 'Peserta Luar',
        'nim' => '888888888',
        'email' => 'peserta@gmail.com',
        'phone' => '081234567890',
        'study_program' => 'Sistem Informasi',
        'cohort_year' => 2023,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertRedirect('/app');
    expect(User::where('email', 'peserta@gmail.com')->exists())->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| 6. Upload NDA: Dimensi Gambar Maksimal 6000px & Resize Otomatis
|--------------------------------------------------------------------------
*/
test('upload NDA gambar menolak gambar yang melebihi batas 6000px dengan pesan jelas', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    // Buat gambar buatan dengan dimensi 6001 x 100
    $oversizedImage = UploadedFile::fake()->image('huge.jpg', 6001, 100);

    $response = $this->actingAs($user)->post(route('verification.upload'), [
        'signed_file' => $oversizedImage,
    ]);

    $response->assertSessionHasErrors(['signed_file']);
    $errorMsg = session('errors')->first('signed_file');
    expect($errorMsg)->toContain('Dimensi gambar terlalu besar (maksimal 6000x6000 piksel');
});

test('upload NDA gambar valid dalam batas 6000px diproses dan dihitung hash sha256', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $validImage = UploadedFile::fake()->image('valid_scan.jpg', 2000, 1500);

    $response = $this->actingAs($user)->post(route('verification.upload'), [
        'signed_file' => $validImage,
    ]);

    $response->assertRedirect(route('verification.index'));
    $submission = NdaSubmission::where('user_id', $user->id)->first();
    expect($submission)->not->toBeNull()
        ->and($submission->signed_file_sha256)->toHaveLength(64)
        ->and(Storage::disk('private')->exists($submission->signed_file_path))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| 7. Gabung Tim: Lock & Penolakan Saat Tim Penuh (Race Condition Test)
|--------------------------------------------------------------------------
*/
test('dua anggota berebut slot terakhir: anggota pertama diterima, anggota kedua ditolak karena tim penuh', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_team_size', 2);

    $leader = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::create([
        'name' => 'Tim Slot Terakhir',
        'leader_id' => $leader->id,
        'invite_code' => 'LASTSLOT',
    ]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader']);

    $userA = User::factory()->create(['email_verified_at' => now()]);
    $userB = User::factory()->create(['email_verified_at' => now()]);

    // User A gabung terlebih dahulu (slot ke-2 terisi)
    $responseA = $this->actingAs($userA)->post(route('teams.join'), [
        'invite_code' => 'LASTSLOT',
    ]);
    $responseA->assertRedirect(route('teams.index'));
    expect($team->members()->count())->toBe(2);

    // User B mencoba gabung pada slot yang sama -> Ditolak
    $responseB = $this->actingAs($userB)->post(route('teams.join'), [
        'invite_code' => 'LASTSLOT',
    ]);
    $responseB->assertSessionHasErrors(['invite_code']);
    expect($userB->fresh()->teamMember)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| 8. Throttle Endpoint & Logika Ketua Tim Keluar / Reassign
|--------------------------------------------------------------------------
*/
test('throttle endpoint gabung tim dibatasi 10 per menit per pengguna', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    for ($i = 1; $i <= 10; $i++) {
        $response = $this->actingAs($user)->post(route('teams.join'), [
            'invite_code' => 'NONEXIST',
        ]);
        expect($response->status())->not->toBe(429);
    }

    // Permintaan ke-11 dalam menit yang sama harus di-throttle (HTTP 429)
    $throttled = $this->actingAs($user)->post(route('teams.join'), [
        'invite_code' => 'NONEXIST',
    ]);
    expect($throttled->status())->toBe(429);
});

test('throttle endpoint registrasi dibatasi 10 per jam per IP', function () {
    for ($i = 1; $i <= 10; $i++) {
        $response = $this->post('/register', [
            'name' => "Peserta {$i}",
            'nim' => "21000000{$i}",
            'email' => "peserta{$i}@ubbg.ac.id",
            'phone' => '081234567890',
            'study_program' => 'Informatika',
            'cohort_year' => 2022,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);
        expect($response->status())->not->toBe(429);
    }

    // Registrasi ke-11 dari IP yang sama harus di-throttle (HTTP 429)
    $throttled = $this->post('/register', [
        'name' => 'Peserta 11',
        'nim' => '2100000011',
        'email' => 'peserta11@ubbg.ac.id',
        'phone' => '081234567890',
        'study_program' => 'Informatika',
        'cohort_year' => 2022,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);
    expect($throttled->status())->toBe(429);
});

test('ketua tim tidak bisa keluar sebelum menunjuk ketua baru jika ada anggota lain', function () {
    $leader = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::create(['name' => 'Tim Multi', 'leader_id' => $leader->id, 'invite_code' => 'MULTITM1']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader']);

    $member = User::factory()->create(['email_verified_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member']);

    // Leader mencoba langsung keluar
    $response = $this->actingAs($leader)->post(route('teams.leave'));
    $response->assertSessionHas('error', 'Sebagai ketua tim, Anda tidak bisa keluar sebelum menunjuk ketua baru.');
    expect($leader->fresh()->teamMember)->not->toBeNull();

    // Leader mengalihkan kepemimpinan kepada member
    $transferResponse = $this->actingAs($leader)->post(route('teams.transfer-leadership'), [
        'new_leader_id' => $member->id,
    ]);
    $transferResponse->assertRedirect(route('teams.index'));
    expect($team->fresh()->leader_id)->toBe($member->id);

    // Sekarang mantan leader bisa keluar
    $leaveResponse = $this->actingAs($leader)->post(route('teams.leave'));
    $leaveResponse->assertRedirect(route('teams.index'));
    expect($leader->fresh()->teamMember)->toBeNull();
});

test('admin dapat memindahkan ketua tim ke anggota lain', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'must_change_password' => false,
        'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('admin');

    $leader = User::factory()->create(['email_verified_at' => now()]);
    $team = Team::create(['name' => 'Tim Admin Pindah', 'leader_id' => $leader->id, 'invite_code' => 'ADMTRANS']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader']);

    $member = User::factory()->create(['email_verified_at' => now()]);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $member->id, 'role' => 'member']);

    $response = $this->actingAs($admin)->post(route('teams.admin-reassign-leader', $team->id), [
        'new_leader_id' => $member->id,
    ]);

    $response->assertSessionHas('success');
    expect($team->fresh()->leader_id)->toBe($member->id);
    expect(TeamMember::where('team_id', $team->id)->where('user_id', $member->id)->first()->role)->toBe('leader');
});

/*
|--------------------------------------------------------------------------
| 9. Persetujuan Aturan Wajib Diulang Saat rules_version Dinaikkan
|--------------------------------------------------------------------------
*/
test('persetujuan aturan wajib diulang saat rules_version dinaikkan oleh panitia', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('rules_version', '1');

    $leader = User::factory()->create([
        'email_verified_at' => now(),
        'rules_accepted_version' => '1',
        'rules_accepted_at' => now(),
    ]);

    $team = Team::create(['name' => 'Tim Rule Test', 'leader_id' => $leader->id, 'invite_code' => 'RULETEST']);
    TeamMember::create(['team_id' => $team->id, 'user_id' => $leader->id, 'role' => 'leader']);

    NdaSubmission::create([
        'user_id' => $leader->id,
        'team_id' => $team->id,
        'document_number' => 'NDA-2026-0001-TEST',
        'template_version' => '1.0',
        'signed_file_path' => 'nda/test.pdf',
        'signed_file_sha256' => hash('sha256', 'dummy'),
        'status' => 'approved',
    ]);

    // Leader awalnya approved karena menyetujui versi 1
    expect($leader->isApprovedParticipant('1'))->toBeTrue();
    $this->actingAs($leader)->get(route('scope.index'))->assertOk();

    // Panitia menaikkan rules_version menjadi 2
    $settings->set('rules_version', '2');

    // Akses terkunci: dialihkan ke /app/aturan
    $response = $this->actingAs($leader)->get(route('scope.index'));
    $response->assertRedirect(route('rules.index'));
    $response->assertSessionHas('error', 'Akses terkunci: Versi Aturan Main telah diperbarui. Silakan setujui Aturan Main terbaru.');

    expect($leader->fresh()->isApprovedParticipant())->toBeFalse();

    // Leader menyetujui aturan versi 2
    $agreeResponse = $this->actingAs($leader)->post(route('rules.accept'), [
        'agree' => '1',
    ]);
    $agreeResponse->assertRedirect(route('rules.index'));

    expect($leader->fresh()->rules_accepted_version)->toBe('2')
        ->and($leader->fresh()->isApprovedParticipant())->toBeTrue();

    // Akses scope pulih kembali
    $this->actingAs($leader)->get(route('scope.index'))->assertOk();
});
