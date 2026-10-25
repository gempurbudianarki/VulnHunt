<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('vulnhunt:preflight gagal pada environment non-production dengan detail pemeriksaan yang jelas', function () {
    // Jalankan preflight pada environment testing/local default
    $exitCode = Artisan::call('vulnhunt:preflight');
    $output = Artisan::output();

    expect($exitCode)->toBe(1);
    expect($output)->toContain('UBBG VULNHUNT PRODUCTION PREFLIGHT AUDIT');
    expect($output)->toContain('PREFLIGHT PRODUKSI GAGAL');
    expect($output)->toContain('FAIL');
});

test('vulnhunt:preflight lulus dengan exit code 0 saat seluruh 12 kriteria produksi terpenuhi', function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('triager');

    // Simulasikan seluruh konfigurasi standar produksi
    Config::set('app.env', 'production');
    Config::set('app.debug', false);
    Config::set('app.url', 'https://vulnhunt.ubbg.ac.id');
    Config::set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    Config::set('mail.default', 'smtp');
    Config::set('queue.default', 'database');
    Config::set('cache.default', 'database');
    Config::set('session.secure', true);
    Config::set('security.csp_report_only', false);

    // Buat akun staf resmi yang sudah ganti password
    $cleanAdmin = User::factory()->create([
        'email' => 'admin.official@ubbg.ac.id',
        'must_change_password' => false,
    ]);
    $cleanAdmin->assignRole('admin');

    app()->detectEnvironment(fn () => 'production');

    try {
        $exitCode = Artisan::call('vulnhunt:preflight');
        $output = Artisan::output();

        expect($exitCode)->toBe(0);
        expect($output)->toContain('PREFLIGHT PRODUKSI LULUS');
        expect($output)->not->toContain('FAIL');
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});
