<?php

namespace App\Providers;

use App\Services\CompetitionSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cegah N+1 Lazy Loading pada mode non-production
        Model::preventLazyLoading(! app()->isProduction());

        Gate::policy(\Spatie\Activitylog\Models\Activity::class, \App\Policies\ActivityPolicy::class);

        // Rate Limiter Fase 2 (Spesifikasi Bagian 8.9):
        // 1. Kirim laporan: 10 per jam per tim
        RateLimiter::for('reports', function (Request $request) {
            $user = $request->user();
            $team = $user?->currentTeam();
            $teamId = $team?->id ?? ($user?->id ?: $request->ip());
            $limitPerHour = (int) app(CompetitionSettings::class)->get('report_rate_limit_per_hour', 10);

            return Limit::perHour($limitPerHour)->by('reports:team:'.$teamId);
        });

        // 2. Upload: 20 per menit per pengguna
        RateLimiter::for('uploads', function (Request $request) {
            if (empty($request->allFiles())) {
                return Limit::none();
            }

            $user = $request->user();
            $userId = $user?->id ?: $request->ip();
            $limitPerMinute = (int) app(CompetitionSettings::class)->get('upload_rate_limit_per_minute', 20);

            return Limit::perMinute($limitPerMinute)->by('uploads:user:'.$userId);
        });

        // 3. Komentar: 30 per menit per pengguna
        RateLimiter::for('comments', function (Request $request) {
            $user = $request->user();
            $userId = $user?->id ?: $request->ip();
            $limitPerMinute = (int) app(CompetitionSettings::class)->get('comment_rate_limit_per_minute', 30);

            return Limit::perMinute($limitPerMinute)->by('comments:user:'.$userId);
        });
    }
}
