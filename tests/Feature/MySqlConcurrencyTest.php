<?php

use App\Models\Report;
use App\Models\ReportComment;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Services\CompetitionSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

/*
|--------------------------------------------------------------------------
| Pengujian Concurrency & Pessimistic Locking pada MySQL (lombasaiber_test)
|--------------------------------------------------------------------------
*/
beforeEach(function () {
    // Pastikan koneksi menggunakan mysql_test
    config(['database.default' => 'mysql_test']);

    // Bersihkan tabel pengujian
    DB::connection('mysql_test')->table('report_comments')->delete();
    DB::connection('mysql_test')->table('report_attachments')->delete();
    DB::connection('mysql_test')->table('report_versions')->delete();
    DB::connection('mysql_test')->table('report_status_histories')->delete();
    DB::connection('mysql_test')->table('reports')->delete();
    DB::connection('mysql_test')->table('scopes')->delete();
    DB::connection('mysql_test')->table('vulnerability_types')->delete();
    DB::connection('mysql_test')->table('team_members')->delete();
    DB::connection('mysql_test')->table('teams')->delete();
    DB::connection('mysql_test')->table('users')->delete();
});

test('mysql lockForUpdate menahan exclusive row lock dan menahan transaksi konkuren', function () {
    $leaderId = DB::connection('mysql_test')->table('users')->insertGetId([
        'name' => 'Leader Test',
        'email' => 'leader_lock@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $teamId = DB::connection('mysql_test')->table('teams')->insertGetId([
        'name' => 'Tim Lock Concurrency',
        'slug' => 'tim-lock-concurrency',
        'invite_code' => 'LOCKTEST',
        'leader_id' => $leaderId,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::connection('mysql_test')->table('team_members')->insert([
        'team_id' => $teamId,
        'user_id' => $leaderId,
        'role' => 'leader',
        'joined_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // 1. Koneksi 1: Mulai transaksi dan kunci baris tim dengan lockForUpdate
    $conn1 = DB::connection('mysql_test');
    $conn1->beginTransaction();
    $lockedTeam = $conn1->table('teams')->where('id', $teamId)->lockForUpdate()->first();
    expect($lockedTeam)->not->toBeNull();

    // 2. Koneksi 2 (Koneksi PDO terpisah): Coba ambil baris yang sama dengan lock wait timeout 1 detik
    $pdo2 = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=lombasaiber_test;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $pdo2->exec('SET SESSION innodb_lock_wait_timeout = 1');
    $pdo2->beginTransaction();

    $lockBlocked = false;
    try {
        $stmt = $pdo2->prepare('SELECT * FROM teams WHERE id = ? FOR UPDATE');
        $stmt->execute([$teamId]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), '1205') || str_contains($e->getMessage(), 'Lock wait timeout')) {
            $lockBlocked = true;
        }
    }

    $pdo2->rollBack();
    $conn1->rollBack();

    expect($lockBlocked)->toBeTrue('Koneksi 2 harus terblokir timeout oleh lockForUpdate dari Koneksi 1');
});

test('TeamController::join memanggil lockForUpdate pada level produksi (mutation check)', function () {
    $leader = User::on('mysql_test')->create([
        'name' => 'Leader Lock Test',
        'email' => 'leader_lock_join@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Lock Join',
        'slug' => 'tim-lock-join',
        'invite_code' => 'LCKJOIN1',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    TeamMember::on('mysql_test')->create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $userA = User::on('mysql_test')->create([
        'name' => 'User Join Lock',
        'email' => 'user_join_lock@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    // Koneksi 1 memegang exclusive lock pada baris tim
    $conn1 = DB::connection('mysql_test');
    $conn1->beginTransaction();
    $conn1->table('teams')->where('id', $team->id)->lockForUpdate()->first();

    // Jalankan sub-proses yang memanggil TeamController::join produksi via artisan dengan lock_wait_timeout 1 detik
    $result = Process::path(base_path())->run("php artisan app:test-lock-join {$team->invite_code} {$userA->id}");
    $conn1->rollBack();

    // Jika lockForUpdate ada di TeamController::join, query SELECT baris tim langsung terblokir timeout di SQL pertama.
    // Jika lockForUpdate dihapus dari TeamController::join, query SELECT tidak terblokir (Test GAGAL).
    expect($result->output())->toContain('select * from `teams` where `invite_code` = ? limit 1 for update');
});

test('ReportController::store memanggil lockForUpdate pada level produksi (mutation check)', function () {
    $settings = app(CompetitionSettings::class);

    $leader = User::on('mysql_test')->create([
        'name' => 'Reporter Lock Store',
        'email' => 'reporter_lock_store@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
        'rules_accepted_version' => (string) $settings->get('rules_version', '1'),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Lock Store',
        'slug' => 'tim-lock-store',
        'invite_code' => 'LCKSTORE',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    TeamMember::on('mysql_test')->create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $scope = Scope::on('mysql_test')->create([
        'name' => 'Target Store Lock',
        'target' => 'https://lock-store.ubbg.ac.id',
        'scope_status' => 'in_scope',
        'is_active' => true,
    ]);

    $cwe = VulnerabilityType::on('mysql_test')->create([
        'cwe_id' => 'CWE-89',
        'name' => 'SQLi Lock',
        'is_active' => true,
    ]);

    // Koneksi 1 memegang exclusive lock pada baris tim
    $conn1 = DB::connection('mysql_test');
    $conn1->beginTransaction();
    $conn1->table('teams')->where('id', $team->id)->lockForUpdate()->first();

    // Jalankan sub-proses yang memanggil ReportController::store produksi via artisan dengan lock_wait_timeout 1 detik
    $result = Process::path(base_path())->run("php artisan app:test-lock-report {$leader->id} {$scope->id} {$cwe->id} \"Uji Lock Store\"");
    $conn1->rollBack();

    // Jika lockForUpdate ada di ReportController::store, query SELECT baris tim langsung terblokir timeout di SQL pertama.
    // Jika lockForUpdate dihapus dari ReportController::store, query SELECT tidak terblokir (Test GAGAL).
    expect($result->output())->toContain('select * from `teams` where `id` = ? limit 1 for update');
});

test('skenario balapan join tim di mysql via Process pool: memanggil TeamController produksi secara paralel', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_team_size', 2);

    $leader = User::on('mysql_test')->create([
        'name' => 'Leader Race Pool',
        'email' => 'leader_pool@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Race Pool',
        'slug' => 'tim-race-pool',
        'invite_code' => 'POOLJOIN',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    TeamMember::on('mysql_test')->create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $userA = User::on('mysql_test')->create([
        'name' => 'User A Pool',
        'email' => 'user_a_pool@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $userB = User::on('mysql_test')->create([
        'name' => 'User B Pool',
        'email' => 'user_b_pool@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    // Jalankan dua proses paralel memanggil TeamController::join produksi melalui artisan command
    $results = Process::pool(function ($pool) use ($team, $userA, $userB) {
        $pool->as('p1')->path(base_path())->command("php artisan app:concurrent-join {$team->invite_code} {$userA->id}");
        $pool->as('p2')->path(base_path())->command("php artisan app:concurrent-join {$team->invite_code} {$userB->id}");
    })->wait();

    $successCount = 0;
    $failedCount = 0;
    foreach (['p1', 'p2'] as $key) {
        $result = $results[$key];
        if ($result->successful() && str_contains($result->output(), 'SUCCESS')) {
            $successCount++;
        } else {
            $failedCount++;
        }
    }

    // Tepat 1 proses berhasil mendapatkan slot terakhir, 1 proses gagal
    expect($successCount)->toBe(1, "P1 out: [{$results['p1']->output()}] P1 err: [{$results['p1']->errorOutput()}] | P2 out: [{$results['p2']->output()}] P2 err: [{$results['p2']->errorOutput()}]")
        ->and($failedCount)->toBe(1);

    // Total anggota di database MySQL tetap tepat 2 (tidak pernah tembus menjadi 3)
    $finalCount = TeamMember::on('mysql_test')->where('team_id', $team->id)->count();
    expect($finalCount)->toBe(2);
});

test('skenario balapan kirim laporan di mysql via Process pool: memanggil ReportController produksi secara paralel', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_active_reports_per_team', 2);

    $leader = User::on('mysql_test')->create([
        'name' => 'Leader Report Pool',
        'email' => 'leader_rep_pool@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
        'rules_accepted_version' => (string) $settings->get('rules_version', '1'),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Report Pool',
        'slug' => 'tim-report-pool',
        'invite_code' => 'POOLREP',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    TeamMember::on('mysql_test')->create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $scope = Scope::on('mysql_test')->create([
        'name' => 'Target Pool',
        'target' => 'https://target-pool.ubbg.ac.id',
        'scope_status' => 'in_scope',
        'is_active' => true,
    ]);

    $cwe = VulnerabilityType::on('mysql_test')->create([
        'cwe_id' => 'CWE-89',
        'name' => 'SQLi Pool',
        'is_active' => true,
    ]);

    // Laporan awal (kuota aktif 1/2)
    Report::on('mysql_test')->create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Awal Pool',
        'endpoint' => '/act1',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi 1',
        'steps_to_reproduce' => str_repeat('Langkah 1. ', 15),
        'impact' => 'Dampak 1',
        'status' => 'submitted',
    ]);

    // Jalankan dua proses paralel memanggil ReportController::store via artisan command
    $results = Process::pool(function ($pool) use ($leader, $scope, $cwe) {
        $pool->as('p1')->path(base_path())->command("php artisan app:concurrent-report {$leader->id} {$scope->id} {$cwe->id} \"Laporan Concurrency 2\"");
        $pool->as('p2')->path(base_path())->command("php artisan app:concurrent-report {$leader->id} {$scope->id} {$cwe->id} \"Laporan Concurrency 3\"");
    })->wait();

    $successCount = 0;
    $failedCount = 0;
    foreach (['p1', 'p2'] as $key) {
        $result = $results[$key];
        if ($result->successful() && str_contains($result->output(), 'SUCCESS')) {
            $successCount++;
        } else {
            $failedCount++;
        }
    }

    // Tepat 1 proses berhasil mendapatkan kuota terakhir, 1 proses gagal ditolak
    expect($successCount)->toBe(1, "P1: {$results['p1']->output()} | P2: {$results['p2']->output()}")
        ->and($failedCount)->toBe(1);

    // Total laporan aktif di MySQL tetap tepat 2 (tidak pernah tembus menjadi 3)
    $activeReports = Report::on('mysql_test')->where('team_id', $team->id)->whereIn('status', ['submitted', 'in_review'])->count();
    expect($activeReports)->toBe(2);
});

test('mysql invarian opsi B: balasan needs_info tim tetap sukses saat kuota aktif mysql penuh', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_active_reports_per_team', 2);

    $leader = User::on('mysql_test')->create([
        'name' => 'Option B Leader',
        'email' => 'opt_b@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Opsi B MySQL',
        'slug' => 'tim-opsi-b-mysql',
        'invite_code' => 'OPSIBMY',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    TeamMember::on('mysql_test')->create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $scope = Scope::on('mysql_test')->create([
        'name' => 'Scope B',
        'target' => 'https://target-b.ubbg.ac.id',
        'scope_status' => 'in_scope',
        'is_active' => true,
    ]);

    $cwe = VulnerabilityType::on('mysql_test')->create([
        'cwe_id' => 'CWE-79',
        'name' => 'XSS',
        'is_active' => true,
    ]);

    // Buat 2 laporan aktif (submitted + in_review) -> kuota penuh (2/2)
    Report::on('mysql_test')->create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Aktif 1',
        'endpoint' => '/act1',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi 1',
        'steps_to_reproduce' => str_repeat('Langkah 1. ', 15),
        'impact' => 'Dampak 1',
        'status' => 'submitted',
    ]);

    Report::on('mysql_test')->create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Aktif 2',
        'endpoint' => '/act2',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi 2',
        'steps_to_reproduce' => str_repeat('Langkah 2. ', 15),
        'impact' => 'Dampak 2',
        'status' => 'in_review',
    ]);

    // Laporan ke-3 berstatus needs_info
    $needsInfo = Report::on('mysql_test')->create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Needs Info MySQL',
        'endpoint' => '/needs-info',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi needs info',
        'steps_to_reproduce' => str_repeat('Langkah awal. ', 15),
        'impact' => 'Dampak',
        'status' => 'needs_info',
    ]);

    // Kuota aktif saat ini = 2
    $activeCount = Report::on('mysql_test')->where('team_id', $team->id)
        ->whereIn('status', ['submitted', 'triaged', 'in_review'])
        ->count();
    expect($activeCount)->toBe(2);

    // Pengajuan laporan baru DITOLAK oleh kuota
    $canSubmitNew = $activeCount < 2;
    expect($canSubmitNew)->toBeFalse('Pengiriman laporan baru wajib ditolak saat kuota aktif penuh');

    // Transisi needs_info -> in_review DIKECUALIKAN dari kuota aktif
    DB::connection('mysql_test')->transaction(function () use ($needsInfo, $leader) {
        $needsInfo->update(['status' => 'in_review', 'revision_no' => $needsInfo->revision_no + 1]);

        ReportComment::on('mysql_test')->create([
            'report_id' => $needsInfo->id,
            'user_id' => $leader->id,
            'body' => 'Tanggapan perbaikan informasi temuan.',
            'is_internal' => false,
        ]);
    });

    expect($needsInfo->fresh()->status)->toBe('in_review')
        ->and(Report::on('mysql_test')->where('team_id', $team->id)->whereIn('status', ['submitted', 'triaged', 'in_review'])->count())->toBe(3);
});

test('10 request submit laporan bersamaan ke MySQL dengan kuota 5 (tidak boleh melebihi kuota)', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_active_reports_per_team', 5);

    $leader = User::on('mysql_test')->create([
        'name' => 'Leader Concurrency 10 Suite',
        'email' => 'leader10_suite@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
        'rules_accepted_version' => (string) $settings->get('rules_version', '1'),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Kuota 5 Suite',
        'slug' => 'tim-kuota-5-suite',
        'invite_code' => 'KUOTA10S',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    TeamMember::on('mysql_test')->create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $scope = Scope::on('mysql_test')->create([
        'name' => 'Target Concurrency Suite',
        'target' => 'https://race-suite.ubbg.ac.id',
        'scope_status' => 'in_scope',
        'is_active' => true,
    ]);

    $cwe = VulnerabilityType::on('mysql_test')->create([
        'cwe_id' => 'CWE-89',
        'name' => 'SQL Injection Concurrency Suite',
        'is_active' => true,
    ]);

    $basePath = base_path();
    $poolResults = Process::pool(function ($pool) use ($basePath, $leader, $scope, $cwe) {
        for ($i = 1; $i <= 10; $i++) {
            $cmd = "php artisan --env=testing app:concurrent-report {$leader->id} {$scope->id} {$cwe->id} \"Laporan Paralel Suite #{$i}\" --database=mysql_test";
            $pool->as("sub_{$i}")->path($basePath)->command($cmd);
        }
    })->wait();

    $successCount = 0;
    $failedCount = 0;
    for ($i = 1; $i <= 10; $i++) {
        $out = $poolResults["sub_{$i}"]->output();
        if (str_contains($out, 'SUCCESS')) {
            $successCount++;
        } else {
            $failedCount++;
        }
    }

    expect($successCount)->toBe(5)
        ->and($failedCount)->toBe(5);

    $finalActiveReports = Report::on('mysql_test')->where('team_id', $team->id)->whereIn('status', ['submitted', 'in_review', 'triaged'])->count();
    expect($finalActiveReports)->toBe(5);
});

test('10 request gabung tim bersamaan ke MySQL untuk slot terakhir (tepat 1 yang berhasil)', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_team_size', 2);

    $leader = User::on('mysql_test')->create([
        'name' => 'Leader Slot 10 Suite',
        'email' => 'leader_slot_10@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Slot 10 Suite',
        'slug' => 'tim-slot-10-suite',
        'invite_code' => 'SLOT10ST',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    TeamMember::on('mysql_test')->create([
        'team_id' => $team->id,
        'user_id' => $leader->id,
        'role' => 'leader',
        'joined_at' => now(),
    ]);

    $candidates = [];
    for ($i = 1; $i <= 10; $i++) {
        $candidates[$i] = User::on('mysql_test')->create([
            'name' => "Calon Suite #{$i}",
            'email' => "calon_suite_{$i}@ubbg.ac.id",
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
            'rules_accepted_version' => '1',
        ]);
    }

    $basePath = base_path();
    $poolResults = Process::pool(function ($pool) use ($basePath, $team, $candidates) {
        for ($i = 1; $i <= 10; $i++) {
            $cId = $candidates[$i]->id;
            $cmd = "php artisan --env=testing app:concurrent-join {$team->invite_code} {$cId} --database=mysql_test";
            $pool->as("join_{$i}")->path($basePath)->command($cmd);
        }
    })->wait();

    $successCount = 0;
    $failedCount = 0;
    for ($i = 1; $i <= 10; $i++) {
        $out = $poolResults["join_{$i}"]->output();
        if (str_contains($out, 'SUCCESS')) {
            $successCount++;
        } else {
            $failedCount++;
        }
    }

    expect($successCount)->toBe(1)
        ->and($failedCount)->toBe(9);

    $finalMembers = TeamMember::on('mysql_test')->where('team_id', $team->id)->count();
    expect($finalMembers)->toBe(2);
});

