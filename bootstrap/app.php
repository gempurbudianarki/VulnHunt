<?php

use App\Http\Middleware\EnsureParticipantApproved;
use App\Http\Middleware\EnsureStaffSecuritySetup;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'participant.approved' => EnsureParticipantApproved::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);

        $middleware->web(append: [
            EnsureStaffSecuritySetup::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (PostTooLargeException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Ukuran berkas unggahan terlalu besar dan melebihi batas server (post_max_size).'], 413);
            }

            return back()->with('error', 'Ukuran berkas unggahan terlalu besar dan melebihi batas server (post_max_size). Silakan kurangi ukuran berkas Anda.');
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                $contentLength = (int) ($request->server('CONTENT_LENGTH') ?? $request->header('CONTENT_LENGTH', 0));
                if ($contentLength > 0 && empty($request->post())) {
                    if ($request->expectsJson()) {
                        return response()->json(['message' => 'Ukuran total unggahan melebihi batas konfigurasi server (post_max_size).'], 413);
                    }

                    return back()->with('error', 'Ukuran total unggahan melebihi batas konfigurasi server (post_max_size). Silakan unggah berkas yang lebih kecil.');
                }
            }
        });
    })->create();
