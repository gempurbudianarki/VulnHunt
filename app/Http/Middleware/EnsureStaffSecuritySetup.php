<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffSecuritySetup
{
    /**
     * Handle an incoming request.
     * Enforce password change and 2FA TOTP activation on first login for admin & triager.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasAnyRole(['admin', 'triager'])) {
            if ($user->must_change_password || ! $user->hasEnabledTwoFactorAuthentication()) {
                if (! $request->routeIs('security.setup*') && ! $request->routeIs('logout')) {
                    return redirect()->route('security.setup')
                        ->with('status', 'Demi keamanan, Anda wajib mengganti kata sandi awal dan mengaktifkan 2FA TOTP.');
                }
            }
        }

        return $next($request);
    }
}
