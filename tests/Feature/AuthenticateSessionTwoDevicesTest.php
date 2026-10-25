<?php

use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::findOrCreate('admin');
    Role::findOrCreate('triager');
    Role::findOrCreate('participant');
});

test('ganti password di sesi A membatalkan sesi B dan mengarahkan sesi B ke login via AuthenticateSession', function () {
    $user = User::factory()->create([
        'email' => 'multisession@ubbg.ac.id',
        'password' => bcrypt('OldPassword123!'),
        'email_verified_at' => now(),
        'rules_accepted_version' => '1',
    ]);
    $user->assignRole('participant');

    // 1. Sesi A login
    $responseA = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'OldPassword123!',
    ]);
    $responseA->assertRedirect();
    $sessionAData = session()->all();

    // 2. Sesi B login (simulasi browser/perangkat kedua)
    $this->flushSession();
    $responseB = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'OldPassword123!',
    ]);
    $responseB->assertRedirect();
    $sessionBData = session()->all();

    // 3. Sesi A mengganti password via UpdateUserPassword
    $this->flushSession();
    session($sessionAData);
    $this->actingAs($user);

    $updater = app(UpdateUserPassword::class);
    $updater->update($user, [
        'current_password' => 'OldPassword123!',
        'password' => 'NewPassword456!@#',
        'password_confirmation' => 'NewPassword456!@#',
    ]);

    // Sesi A tetap dapat mengakses dasbor
    $resA = $this->get(route('dashboard'));
    $resA->assertOk();

    // 4. Sesi B mencoba mengakses halaman terlindungi
    $this->flushSession();
    session($sessionBData);
    $this->actingAs($user->fresh());

    // Middleware AuthenticateSession mendeteksi ketidakcocokan hash dan me-logout sesi B
    $resB = $this->get(route('dashboard'));
    $resB->assertRedirect(route('login'));
});
