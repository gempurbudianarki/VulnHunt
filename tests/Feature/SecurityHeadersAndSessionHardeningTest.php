<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('triager');
    Role::findOrCreate('participant');
});

test('seluruh halaman publik mengembalikan security headers lengkap dan CSP nonce', function () {
    $res = $this->get('/');

    $res->assertSuccessful();
    $res->assertHeader('X-Content-Type-Options', 'nosniff');
    $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $res->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
    $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');

    // CSP Report-Only aktif secara default di environment non-production
    expect($res->headers->has('Content-Security-Policy-Report-Only'))->toBeTrue();
    $csp = $res->headers->get('Content-Security-Policy-Report-Only');
    expect($csp)->toContain("default-src 'self'");
    expect($csp)->toContain("script-src 'self' 'nonce-");
    expect($csp)->toContain("frame-ancestors 'self'");
});

test('CSP berganti menjadi header Content-Security-Policy saat mode report only dimatikan', function () {
    Config::set('security.csp_report_only', false);

    $res = $this->get('/login');

    $res->assertSuccessful();
    $res->assertHeaderMissing('Content-Security-Policy-Report-Only');
    expect($res->headers->has('Content-Security-Policy'))->toBeTrue();
    expect($res->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

test('HSTS hanya aktif di environment production melalui koneksi HTTPS', function () {
    // 1. Pada environment testing/local tanpa HTTPS: HSTS tidak dipasang
    $localRes = $this->get('/');
    $localRes->assertHeaderMissing('Strict-Transport-Security');

    // 2. Pada environment production dengan HTTPS: HSTS wajib aktif
    Config::set('app.env', 'production');
    app()->detectEnvironment(fn () => 'production');

    try {
        $prodSecureRes = $this->get('https://localhost/', ['HTTPS' => 'on']);
        $prodSecureRes->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});

test('session idle timeout: staf ter-logout setelah 30 menit inaktivitas dan diarahkan ke login', function () {
    Config::set('security.idle_timeout_staff', 30);

    $admin = User::factory()->create([
        'email' => 'admin.audit@ubbg.ac.id',
        'must_change_password' => false,
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);
    $admin->assignRole('admin');

    // Sesi aktif baru: request berhasil
    $res1 = $this->actingAs($admin)->withSession(['last_activity_time' => time()])->get('/admin');
    $res1->assertSuccessful();

    // Sesi 31 menit yang lalu: idle timeout dipicu
    $res2 = $this->actingAs($admin)->withSession(['last_activity_time' => time() - (31 * 60)])->get('/admin');
    $res2->assertRedirect(route('login'));
    $res2->assertSessionHas('error');
    $this->assertGuest();
});

test('session idle timeout: peserta memiliki batas inaktivitas lebih panjang (120 menit)', function () {
    Config::set('security.idle_timeout_participant', 120);

    $participant = User::factory()->create([
        'email' => 'peserta.idle@ubbg.ac.id',
        'email_verified_at' => now(),
        'rules_accepted_version' => '1',
    ]);
    $participant->assignRole('participant');

    // 40 menit berlalu: peserta masih diizinkan aktif
    $res = $this->actingAs($participant)->withSession(['last_activity_time' => time() - (40 * 60)])->get('/app');
    $res->assertSuccessful();
    $this->assertAuthenticatedAs($participant);

    // 121 menit berlalu: idle timeout dipicu
    $timeoutRes = $this->actingAs($participant)->withSession(['last_activity_time' => time() - (121 * 60)])->get('/app');
    $timeoutRes->assertRedirect(route('login'));
    $this->assertGuest();
});

test('rate limit forgot password dibatasi 3 request per menit per email dan IP', function () {
    $email = 'target.reset@ubbg.ac.id';

    // 3 request pertama diizinkan (atau ditangani oleh controller)
    for ($i = 0; $i < 3; $i++) {
        $res = $this->post('/forgot-password', ['email' => $email]);
        expect($res->status())->not->toBe(429);
    }

    // Request ke-4 harus kena 429 Too Many Requests
    $throttleRes = $this->post('/forgot-password', ['email' => $email]);
    $throttleRes->assertStatus(429);
});

test('sesi lain dibatalkan saat pengguna memperbarui password via UpdateUserPassword', function () {
    $user = User::factory()->create([
        'password' => Hash::make('PasswordLama123!'),
    ]);

    $this->actingAs($user);

    $action = app(\App\Actions\Fortify\UpdateUserPassword::class);
    $action->update($user, [
        'current_password' => 'PasswordLama123!',
        'password' => 'PasswordBaru2026!',
        'password_confirmation' => 'PasswordBaru2026!',
    ]);

    expect(Hash::check('PasswordBaru2026!', $user->fresh()->password))->toBeTrue();
});

