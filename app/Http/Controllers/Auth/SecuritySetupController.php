<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

class SecuritySetupController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['admin', 'triager'])) {
            return redirect()->route('dashboard');
        }

        if (! $user->must_change_password && $user->hasEnabledTwoFactorAuthentication()) {
            return redirect('/admin');
        }

        $provider = app(TwoFactorAuthenticationProvider::class);

        if (! $user->two_factor_secret) {
            $secret = $provider->generateSecretKey();
            $recoveryCodes = Collection::times(8, fn () => Str::random(10).'-'.Str::random(10))->all();

            $user->forceFill([
                'two_factor_secret' => encrypt($secret),
                'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            ])->save();
        }

        $qrCodeSvg = $user->twoFactorQrCodeSvg();
        $manualSecret = decrypt($user->two_factor_secret);

        return view('auth.security-setup', compact('user', 'qrCodeSvg', 'manualSecret'));
    }

    public function save(Request $request)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['admin', 'triager'])) {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:10', 'confirmed', 'different:current_password'],
            'code' => ['required', 'string', 'size:6'],
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.min' => 'Password baru minimal 10 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
            'code.required' => 'Kode TOTP 6 digit wajib diisi.',
            'code.size' => 'Kode TOTP harus terdiri dari 6 digit.',
        ]);

        $provider = app(TwoFactorAuthenticationProvider::class);
        $secret = decrypt($user->two_factor_secret);

        $valid = $provider->verify($secret, $request->code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi 2FA (TOTP) tidak valid atau telah kedaluwarsa. Pastikan jam perangkat Anda akurat.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return redirect('/admin')->with('status', 'Keamanan akun berhasil dikonfigurasi. Selamat datang di Panel Admin!');
    }
}
