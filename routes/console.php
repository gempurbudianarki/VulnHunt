<?php

use App\Http\Controllers\ReportController;
use App\Http\Controllers\TeamController;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('app:concurrent-join {invite_code} {user_id} {--database=mysql_test}', function () {
    $code = $this->argument('invite_code');
    $userId = (int) $this->argument('user_id');
    $db = $this->option('database');

    config(['database.default' => $db]);
    DB::purge();
    DB::reconnect($db);
    DB::setDefaultConnection($db);

    $user = User::on($db)->findOrFail($userId);

    $request = Request::create(route('teams.join'), 'POST', [
        'invite_code' => $code,
    ]);
    $request->setUserResolver(fn () => $user);

    try {
        $response = app(TeamController::class)->join($request);
        if (session('error')) {
            $this->error('FAILED: '.session('error'));

            return 1;
        }
        $this->info('SUCCESS');

        return 0;
    } catch (Throwable $e) {
        $this->error('FAILED: '.$e->getMessage());

        return 1;
    }
});

Artisan::command('app:test-lock-join {invite_code} {user_id} {--database=mysql_test}', function () {
    $code = $this->argument('invite_code');
    $userId = (int) $this->argument('user_id');
    $db = $this->option('database');

    config(['database.default' => $db]);
    DB::purge();
    DB::reconnect($db);
    DB::setDefaultConnection($db);
    DB::statement('SET SESSION innodb_lock_wait_timeout = 1');

    $user = User::on($db)->findOrFail($userId);

    $request = Request::create(route('teams.join'), 'POST', [
        'invite_code' => $code,
    ]);
    $request->setUserResolver(fn () => $user);

    try {
        app(TeamController::class)->join($request);
        $this->line('NO_LOCK_DETECTED');

        return 0;
    } catch (Throwable $e) {
        $sql = method_exists($e, 'getSql') ? $e->getSql() : $e->getMessage();
        $this->line('TIMED_OUT_SQL: '.$sql);

        return 0;
    }
});

Artisan::command('app:concurrent-report {user_id} {scope_id} {cwe_id} {title} {--database=mysql_test}', function () {
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

    $request = Request::create(route('reports.store'), 'POST', [
        'scope_id' => $scopeId,
        'vulnerability_type_id' => $cweId,
        'title' => $title,
        'endpoint' => '/concurrent-test',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Test concurrency deskripsi temuan',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi valid dan terverifikasi. ', 10),
        'impact' => 'Dampak',
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);
    $request->setUserResolver(fn () => $user);

    try {
        app(ReportController::class)->store($request);
        $this->info('SUCCESS');

        return 0;
    } catch (Throwable $e) {
        $this->error('FAILED: '.$e->getMessage());

        return 1;
    }
});

Artisan::command('app:test-lock-report {user_id} {scope_id} {cwe_id} {title} {--database=mysql_test}', function () {
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

    $request = Request::create(route('reports.store'), 'POST', [
        'scope_id' => $scopeId,
        'vulnerability_type_id' => $cweId,
        'title' => $title,
        'endpoint' => '/concurrent-test',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'description' => 'Test lock store',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi valid dan terverifikasi. ', 10),
        'impact' => 'Dampak',
        'agree_no_privacy_violation' => '1',
        'agree_no_destruction' => '1',
        'agree_in_scope_only' => '1',
    ]);
    $request->setUserResolver(fn () => $user);

    try {
        app(ReportController::class)->store($request);
        $this->line('NO_LOCK_DETECTED');

        return 0;
    } catch (Throwable $e) {
        $sql = method_exists($e, 'getSql') ? $e->getSql() : $e->getMessage();
        $this->line('TIMED_OUT_SQL: '.$sql);

        return 0;
    }
});
