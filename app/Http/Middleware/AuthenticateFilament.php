<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;

class AuthenticateFilament extends FilamentAuthenticate
{
    /**
     * Redirect unauthenticated panel visitors to Fortify login with full 2FA support.
     */
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
