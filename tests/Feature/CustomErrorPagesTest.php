<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Config::set('app.debug', false);
});

test('halaman 404 menampilkan layout clay kustom tanpa jejak debug', function () {
    $res = $this->get('/halaman-pasti-tidak-ada-12345');

    $res->assertStatus(404);
    $res->assertSee('KODE KESALAHAN 404');
    $res->assertSee('Halaman Tidak Ditemukan');
    $res->assertDontSee('Exception');
    $res->assertDontSee('Stack trace');
    $res->assertDontSee('.php:');
});

test('halaman 403 menampilkan layout clay kustom saat otorisasi ditolak', function () {
    Route::get('/test-abort-403', fn () => abort(403));

    $res = $this->get('/test-abort-403');

    $res->assertStatus(403);
    $res->assertSee('KODE KESALAHAN 403');
    $res->assertSee('Akses Dilarang');
    $res->assertDontSee('Exception');
    $res->assertDontSee('Stack trace');
});

test('halaman 500 menangani error internal tanpa membocorkan info server atau database', function () {
    Route::get('/test-crash-500', function () {
        abort(500);
    });

    $res = $this->get('/test-crash-500');

    $res->assertStatus(500);
    $res->assertSee('KODE KESALAHAN 500');
    $res->assertSee('Kesalahan Server Internal');
    $res->assertDontSee('SQLSTATE');
    $res->assertDontSee('Stack trace');
    $res->assertDontSee('vendor/');
});
