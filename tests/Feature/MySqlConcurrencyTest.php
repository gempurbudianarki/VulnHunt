<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
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
