<?php

use App\Models\Team;
use App\Models\User;
use App\Services\CvssCalculator;
use App\Services\ReportSubmissionService;
use App\Services\TeamJoinService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('app:concurrent-join {invite_code} {user_id} {--database=mysql_test}', function () {
    if (! app()->environment('testing')) {
        $this->error('Perintah ini hanya diizinkan dijalankan pada environment testing.');

        return 1;
    }

    $code = $this->argument('invite_code');
    $userId = (int) $this->argument('user_id');
    $db = $this->option('database');

    config(['database.default' => $db]);
    DB::purge();
    DB::reconnect($db);
    DB::setDefaultConnection($db);

    $user = User::on($db)->findOrFail($userId);

    try {
        app(TeamJoinService::class)->join($user, $code);
        $this->info('SUCCESS');

        return 0;
    } catch (Throwable $e) {
        $this->error('FAILED: '.$e->getMessage());

        return 1;
    }
});

Artisan::command('app:test-lock-join {invite_code} {user_id} {--database=mysql_test}', function () {
    if (! app()->environment('testing')) {
        $this->error('Perintah ini hanya diizinkan dijalankan pada environment testing.');

        return 1;
    }

    $code = $this->argument('invite_code');
    $userId = (int) $this->argument('user_id');
    $db = $this->option('database');

    config(['database.default' => $db]);
    DB::purge();
    DB::reconnect($db);
    DB::setDefaultConnection($db);
    DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

    $user = User::on($db)->findOrFail($userId);

    try {
        app(TeamJoinService::class)->join($user, $code);
        $this->line('NO_LOCK_DETECTED');

        return 0;
    } catch (Throwable $e) {
        $sql = method_exists($e, 'getSql') ? $e->getSql() : $e->getMessage();
        $this->line('TIMED_OUT_SQL: '.$sql);

        return 0;
    }
});

Artisan::command('app:concurrent-report {user_id} {scope_id} {cwe_id} {title} {--database=mysql_test}', function () {
    if (! app()->environment('testing')) {
        $this->error('Perintah ini hanya diizinkan dijalankan pada environment testing.');

        return 1;
    }

    $userId = (int) $this->argument('user_id');
    $scopeId = (int) $this->argument('scope_id');
    $cweId = (int) $this->argument('cwe_id');
    $title = $this->argument('title');
    $db = $this->option('database');

    config(['database.default' => $db]);
    DB::purge();
    DB::reconnect($db);
    DB::setDefaultConnection($db);

    $user = User::on($db)->findOrFail($userId);
    $team = Team::on($db)->where('leader_id', $user->id)->first() ?? $user->currentTeam();

    $cvss = CvssCalculator::calculate('CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H');

    $data = [
        'scope_id' => $scopeId,
        'vulnerability_type_id' => $cweId,
        'title' => $title,
        'endpoint' => '/concurrent-test',
        'parameter' => null,
        'description' => 'Test concurrency deskripsi temuan',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi valid dan terverifikasi. ', 10),
        'impact' => 'Dampak',
        'remediation' => 'Remediasi',
    ];

    try {
        app(ReportSubmissionService::class)->submit($user, $team, $data, $cvss);
        $this->info('SUCCESS');

        return 0;
    } catch (Throwable $e) {
        $this->error('FAILED: '.$e->getMessage());

        return 1;
    }
});

Artisan::command('app:test-lock-report {user_id} {scope_id} {cwe_id} {title} {--database=mysql_test}', function () {
    if (! app()->environment('testing')) {
        $this->error('Perintah ini hanya diizinkan dijalankan pada environment testing.');

        return 1;
    }

    $userId = (int) $this->argument('user_id');
    $scopeId = (int) $this->argument('scope_id');
    $cweId = (int) $this->argument('cwe_id');
    $title = $this->argument('title');
    $db = $this->option('database');

    config(['database.default' => $db]);
    DB::purge();
    DB::reconnect($db);
    DB::setDefaultConnection($db);
    DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

    $user = User::on($db)->findOrFail($userId);
    $team = Team::on($db)->where('leader_id', $user->id)->first() ?? $user->currentTeam();

    $cvss = CvssCalculator::calculate('CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H');

    $data = [
        'scope_id' => $scopeId,
        'vulnerability_type_id' => $cweId,
        'title' => $title,
        'endpoint' => '/concurrent-test',
        'parameter' => null,
        'description' => 'Test lock store',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi valid dan terverifikasi. ', 10),
        'impact' => 'Dampak',
        'remediation' => 'Remediasi',
    ];

    try {
        app(ReportSubmissionService::class)->submit($user, $team, $data, $cvss);
        $this->line('NO_LOCK_DETECTED');

        return 0;
    } catch (Throwable $e) {
        $sql = method_exists($e, 'getSql') ? $e->getSql() : $e->getMessage();
        $this->line('TIMED_OUT_SQL: '.$sql);

        return 0;
    }
});
