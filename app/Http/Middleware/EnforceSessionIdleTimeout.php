<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnforceSessionIdleTimeout
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $request->hasSession()) {
            $user = $request->user();
            $isStaff = $user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['admin', 'triager']);

            // Timeout dalam menit: staf 30 menit, peserta 120 menit
            $timeoutMinutes = $isStaff
                ? (int) config('security.idle_timeout_staff', 30)
                : (int) config('security.idle_timeout_participant', 120);

            $timeoutSeconds = $timeoutMinutes * 60;
            $lastActivity = $request->session()->get('last_activity_time');

            if ($lastActivity && (time() - $lastActivity) > $timeoutSeconds) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Sesi Anda telah berakhir karena tidak ada aktivitas (idle timeout). Silakan masuk kembali.',
                    ], 401);
                }

                return redirect()->route('login')->with('error', 'Sesi Anda telah berakhir karena tidak ada aktivitas. Demi keamanan, silakan masuk kembali.');
            }

            $request->session()->put('last_activity_time', time());
        }

        return $next($request);
    }
}
