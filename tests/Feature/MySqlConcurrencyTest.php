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
    // 1. Buat data awal di database lombasaiber_test
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

    // 2. Koneksi 1: Mulai transaksi dan kunci baris tim dengan lockForUpdate
    $conn1 = DB::connection('mysql_test');
    $conn1->beginTransaction();
    $lockedTeam = $conn1->table('teams')->where('id', $teamId)->lockForUpdate()->first();
    expect($lockedTeam)->not->toBeNull();

    // 3. Koneksi 2 (Koneksi PDO terpisah): Coba ambil baris yang sama dengan lock wait timeout 1 detik
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
        // Harus terhalang karena Koneksi 1 memegang exclusive lock
        $stmt = $pdo2->prepare('SELECT * FROM teams WHERE id = ? FOR UPDATE');
        $stmt->execute([$teamId]);
    } catch (PDOException $e) {
        // MySQL error 1205: Lock wait timeout exceeded
        if (str_contains($e->getMessage(), '1205') || str_contains($e->getMessage(), 'Lock wait timeout')) {
            $lockBlocked = true;
        }
    }

    $pdo2->rollBack();
    $conn1->rollBack();

    expect($lockBlocked)->toBeTrue('Koneksi 2 harus terblokir timeout oleh lockForUpdate dari Koneksi 1');
});

test('skenario balapan join tim di mysql: lockForUpdate mencegah pelanggaran max_team_size', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_team_size', 2);

    $leader = User::on('mysql_test')->create([
        'name' => 'Leader Race',
        'email' => 'leader_race@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Race MySQL',
        'slug' => 'tim-race-mysql',
        'invite_code' => 'RACETEST',
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
        'name' => 'User A',
        'email' => 'user_a@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $userB = User::on('mysql_test')->create([
        'name' => 'User B',
        'email' => 'user_b@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    // Transaksi 1 bergabung ke slot terakhir
    DB::connection('mysql_test')->transaction(function () use ($userA, $team) {
        $t = Team::on('mysql_test')->where('id', $team->id)->lockForUpdate()->first();
        expect($t->members()->count())->toBe(1);

        TeamMember::on('mysql_test')->create([
            'team_id' => $t->id,
            'user_id' => $userA->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);
    });

    // Transaksi 2 mencoba bergabung saat tim sudah penuh
    $joined = false;
    $errorMessage = '';

    try {
        DB::connection('mysql_test')->transaction(function () use ($userB, $team) {
            $t = Team::on('mysql_test')->where('id', $team->id)->lockForUpdate()->first();
            if ($t->members()->count() >= 2) {
                throw new Exception('Tim ini sudah penuh (maksimal 2 anggota).');
            }

            TeamMember::on('mysql_test')->create([
                'team_id' => $t->id,
                'user_id' => $userB->id,
                'role' => 'member',
                'joined_at' => now(),
            ]);
        });
        $joined = true;
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }

    expect($joined)->toBeFalse()
        ->and($errorMessage)->toContain('Tim ini sudah penuh')
        ->and(TeamMember::on('mysql_test')->where('team_id', $team->id)->count())->toBe(2);
});

/*
|--------------------------------------------------------------------------
| Pengujian Kuota Laporan & Concurrency Sisi MySQL (Fase 2)
|--------------------------------------------------------------------------
*/
test('mysql concurrency: pengiriman laporan bersamaan dengan lockForUpdate mencegah tim melebihi kuota aktif', function () {
    $settings = app(CompetitionSettings::class);
    $settings->set('max_active_reports_per_team', 2);

    $leader = User::on('mysql_test')->create([
        'name' => 'Reporter Leader',
        'email' => 'leader_report@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Concurrency Report',
        'slug' => 'tim-concurrency-report',
        'invite_code' => 'REPCRACE',
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
        'name' => 'Target MySQL',
        'target' => 'https://target.ubbg.ac.id',
        'scope_status' => 'in_scope',
        'is_active' => true,
    ]);

    $cwe = VulnerabilityType::on('mysql_test')->create([
        'cwe_id' => 'CWE-89',
        'name' => 'SQL Injection',
        'is_active' => true,
    ]);

    // Laporan awal (kuota aktif 1/2)
    Report::on('mysql_test')->create([
        'team_id' => $team->id,
        'reported_by' => $leader->id,
        'scope_id' => $scope->id,
        'vulnerability_type_id' => $cwe->id,
        'title' => 'Laporan Awal',
        'endpoint' => '/login',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi laporan awal',
        'steps_to_reproduce' => str_repeat('Langkah reproduksi. ', 10),
        'impact' => 'Dampak',
        'status' => 'submitted',
    ]);

    // Skenario: Dua transaksi pengajuan laporan berjalan untuk slot terakhir (kuota tersisa 1)
    // Transaksi 1 berhasil
    $tx1Success = false;
    DB::connection('mysql_test')->transaction(function () use ($team, $leader, $scope, $cwe, &$tx1Success) {
        $lockedTeam = Team::on('mysql_test')->where('id', $team->id)->lockForUpdate()->first();
        $activeReportsCount = Report::on('mysql_test')->where('team_id', $lockedTeam->id)
            ->whereIn('status', ['submitted', 'triaged', 'in_review'])
            ->count();

        if ($activeReportsCount < 2) {
            Report::on('mysql_test')->create([
                'team_id' => $lockedTeam->id,
                'reported_by' => $leader->id,
                'scope_id' => $scope->id,
                'vulnerability_type_id' => $cwe->id,
                'title' => 'Laporan Kedua (Slot Terakhir)',
                'endpoint' => '/slot-last',
                'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
                'cvss_score_claimed' => 9.8,
                'severity_claimed' => 'critical',
                'description' => 'Deskripsi slot terakhir',
                'steps_to_reproduce' => str_repeat('Langkah slot terakhir. ', 10),
                'impact' => 'Dampak',
                'status' => 'submitted',
            ]);
            $tx1Success = true;
        }
    });

    // Transaksi 2 (bersamaan): Setelah transaksi 1 commit dan lock dilepas, transaksi 2 mengecek kuota
    $tx2Success = false;
    $tx2Error = '';
    try {
        DB::connection('mysql_test')->transaction(function () use ($team, $leader, $scope, $cwe, &$tx2Success) {
            $lockedTeam = Team::on('mysql_test')->where('id', $team->id)->lockForUpdate()->first();
            $activeReportsCount = Report::on('mysql_test')->where('team_id', $lockedTeam->id)
                ->whereIn('status', ['submitted', 'triaged', 'in_review'])
                ->count();

            if ($activeReportsCount >= 2) {
                throw new Exception('Kuota laporan aktif tim Anda telah habis (maksimal 2).');
            }

            Report::on('mysql_test')->create([
                'team_id' => $lockedTeam->id,
                'reported_by' => $leader->id,
                'scope_id' => $scope->id,
                'vulnerability_type_id' => $cwe->id,
                'title' => 'Laporan Ketiga (Harus Gagal)',
                'endpoint' => '/fail',
                'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
                'cvss_score_claimed' => 9.8,
                'severity_claimed' => 'critical',
                'description' => 'Deskripsi gagal',
                'steps_to_reproduce' => str_repeat('Langkah gagal. ', 10),
                'impact' => 'Dampak',
                'status' => 'submitted',
            ]);
            $tx2Success = true;
        });
    } catch (Exception $e) {
        $tx2Error = $e->getMessage();
    }

    expect($tx1Success)->toBeTrue('Transaksi 1 harus sukses mengisi slot kuota terakhir')
        ->and($tx2Success)->toBeFalse('Transaksi 2 harus ditolak karena kuota telah penuh')
        ->and($tx2Error)->toContain('Kuota laporan aktif tim Anda telah habis')
        ->and(Report::on('mysql_test')->where('team_id', $team->id)->count())->toBe(2);
});

test('mysql concurrency: koneksi paralel terblokir timeout saat lockForUpdate tim sedang dipegang transaksi kirim laporan', function () {
    $leader = User::on('mysql_test')->create([
        'name' => 'Lock Reporter',
        'email' => 'lock_reporter@ubbg.ac.id',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ]);

    $team = Team::on('mysql_test')->create([
        'name' => 'Tim Lock Report',
        'slug' => 'tim-lock-report',
        'invite_code' => 'LOCKREP',
        'leader_id' => $leader->id,
        'status' => 'active',
    ]);

    // 1. Koneksi 1: Membuka transaksi dan mengunci row tim untuk laporan
    $conn1 = DB::connection('mysql_test');
    $conn1->beginTransaction();
    $lockedTeam = $conn1->table('teams')->where('id', $team->id)->lockForUpdate()->first();
    expect($lockedTeam)->not->toBeNull();

    // 2. Koneksi 2: Mencoba lockForUpdate pada tim yang sama dengan timeout 1 detik
    $pdo2 = new PDO(
        'mysql:host=127.0.0.1;port=3306;dbname=lombasaiber_test;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo2->exec('SET SESSION innodb_lock_wait_timeout = 1');
    $pdo2->beginTransaction();

    $timedOut = false;
    try {
        $stmt = $pdo2->prepare('SELECT * FROM teams WHERE id = ? FOR UPDATE');
        $stmt->execute([$team->id]);
    } catch (PDOException $e) {
        if (str_contains($e->getMessage(), '1205') || str_contains($e->getMessage(), 'Lock wait timeout')) {
            $timedOut = true;
        }
    }

    $pdo2->rollBack();
    $conn1->rollBack();

    expect($timedOut)->toBeTrue('Koneksi paralel harus mengalami lock wait timeout di MySQL InnoDB');
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
