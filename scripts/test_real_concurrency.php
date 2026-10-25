<?php

/**
 * Uji Paralel Kuota Sungguhan (MySQL Concurrency Audit)
 * 
 * Bagian 1: 10 request submit laporan bersamaan ke MySQL untuk satu tim dengan kuota 5.
 *           Menghitung jumlah baris laporan aktif di database (tidak boleh lebih dari 5).
 * Bagian 2: 10 request gabung tim bersamaan ke MySQL untuk slot terakhir.
 *           Menghitung jumlah anggota tim di database (tepat 1 yang berhasil masuk).
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CompetitionSetting;
use App\Models\Report;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Services\CompetitionSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

$db = 'mysql_test';
config(['database.default' => $db]);
DB::purge();
DB::reconnect($db);
DB::setDefaultConnection($db);

$targetDbName = (string) config("database.connections.{$db}.database");
if (! str_ends_with($targetDbName, '_test')) {
    echo "FATAL ERROR: Target database '{$targetDbName}' tidak berakhiran '_test'!\n";
    echo "Skrip ini hanya diizinkan berjalan pada basis data pengujian untuk mencegah kerusakan/kehilangan data.\n";
    exit(1);
}

echo "========================================================================\n";
echo "   UJI PARALEL KUOTA SUNGGUHAN PADA MYSQL (PESSIMISTIC LOCKING AUDIT)   \n";
echo "========================================================================\n";
echo "Waktu Mulai: " . now()->format('Y-m-d H:i:s T') . "\n";
echo "Database Target: " . config("database.connections.{$db}.database") . " on " . config("database.connections.{$db}.host") . "\n";
echo "Server Version: " . DB::connection($db)->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n\n";

// Bersihkan data uji sebelumnya di mysql_test
DB::connection($db)->table('report_comments')->delete();
DB::connection($db)->table('report_attachments')->delete();
DB::connection($db)->table('report_versions')->delete();
DB::connection($db)->table('report_status_histories')->delete();
DB::connection($db)->table('reports')->delete();
DB::connection($db)->table('team_members')->delete();
DB::connection($db)->table('teams')->delete();
DB::connection($db)->table('scopes')->delete();
DB::connection($db)->table('vulnerability_types')->delete();
DB::connection($db)->table('users')->delete();

// Setup Competition Settings untuk DB mysql_test
$settings = app(CompetitionSettings::class);
$settings->set('max_active_reports_per_team', 5);
$settings->set('max_team_size', 2);
$settings->set('rules_version', '1');

// -----------------------------------------------------------------------------
// PENGUJIAN 1: 10 Request Submit Laporan Bersamaan (Kuota 5)
// -----------------------------------------------------------------------------
echo "------------------------------------------------------------------------\n";
echo "BAGIAN 1: 10 Request Submit Laporan Bersamaan ke MySQL (Kuota = 5)\n";
echo "------------------------------------------------------------------------\n";

$leader = User::on($db)->create([
    'name' => 'Leader Concurrency 10',
    'email' => 'leader10@ubbg.ac.id',
    'password' => bcrypt('password123'),
    'email_verified_at' => now(),
    'rules_accepted_version' => '1',
]);

$team = Team::on($db)->create([
    'name' => 'Tim Kuota 5 Paralel',
    'slug' => 'tim-kuota-5-paralel',
    'invite_code' => 'KUOTA005',
    'leader_id' => $leader->id,
    'status' => 'active',
]);

TeamMember::on($db)->create([
    'team_id' => $team->id,
    'user_id' => $leader->id,
    'role' => 'leader',
    'joined_at' => now(),
]);

$scope = Scope::on($db)->create([
    'name' => 'Target Concurrency',
    'target' => 'https://race.ubbg.ac.id',
    'scope_status' => 'in_scope',
    'is_active' => true,
]);

$cwe = VulnerabilityType::on($db)->create([
    'cwe_id' => 'CWE-89',
    'name' => 'SQL Injection Concurrency',
    'is_active' => true,
]);

$initialCount = Report::on($db)->where('team_id', $team->id)->whereIn('status', ['submitted', 'in_review', 'triaged'])->count();
echo "Status Awal Laporan Aktif di Database: {$initialCount} baris (Batas Maksimal: 5)\n";
echo "Meluncurkan 10 sub-proses submit paralel secara serentak via Process::pool...\n\n";

$basePath = base_path();
$poolResults = Process::pool(function ($pool) use ($basePath, $leader, $scope, $cwe) {
    for ($i = 1; $i <= 10; $i++) {
        $cmd = "php artisan --env=testing app:concurrent-report {$leader->id} {$scope->id} {$cwe->id} \"Laporan Paralel #{$i}\" --database=mysql_test";
        $pool->as("sub_{$i}")->path($basePath)->command($cmd);
    }
})->wait();

$successReports = 0;
$rejectedReports = 0;
echo "Hasil Eksekusi 10 Request Submit Paralel:\n";
for ($i = 1; $i <= 10; $i++) {
    $res = $poolResults["sub_{$i}"];
    $out = trim($res->output());
    $err = trim($res->errorOutput());
    $status = str_contains($out, 'SUCCESS') ? 'SUKSES (DITERIMA)' : 'GAGAL (DITOLAK)';
    if (str_contains($out, 'SUCCESS')) {
        $successReports++;
    } else {
        $rejectedReports++;
    }
    echo sprintf("  [Req #%02d] Status: %-18s | Output: %s %s\n", $i, $status, $out, $err ? "[Err: $err]" : "");
}

$finalActiveReports = Report::on($db)->where('team_id', $team->id)->whereIn('status', ['submitted', 'in_review', 'triaged'])->count();
echo "\nRingkasan Hasil Bagian 1:\n";
echo "  - Total Request: 10\n";
echo "  - Request Diterima: {$successReports}\n";
echo "  - Request Ditolak : {$rejectedReports}\n";
echo "  - Jumlah Baris Laporan Aktif di Database: {$finalActiveReports}\n";
echo "  - Batas Kuota: 5\n";

if ($finalActiveReports === 5 && $successReports === 5 && $rejectedReports === 5) {
    echo "  >> VERIFIKASI KUOTA LAPORAN: LOLOS (Tidak melebihi kuota 5, tepat 5 baris di DB)\n\n";
} else {
    echo "  >> VERIFIKASI KUOTA LAPORAN: GAGAL (Jumlah laporan aktif di DB: {$finalActiveReports})\n\n";
    exit(1);
}

// -----------------------------------------------------------------------------
// PENGUJIAN 2: 10 Request Gabung Tim Bersamaan pada Slot Terakhir (Max Size = 2)
// -----------------------------------------------------------------------------
echo "------------------------------------------------------------------------\n";
echo "BAGIAN 2: 10 Request Gabung Tim Bersamaan untuk Slot Terakhir (Slot Sisa = 1)\n";
echo "------------------------------------------------------------------------\n";

$joinLeader = User::on($db)->create([
    'name' => 'Leader Tim Gabung',
    'email' => 'leader_join_race@ubbg.ac.id',
    'password' => bcrypt('password123'),
    'email_verified_at' => now(),
]);

$joinTeam = Team::on($db)->create([
    'name' => 'Tim Slot Terakhir',
    'slug' => 'tim-slot-terakhir',
    'invite_code' => 'SLOTRACE',
    'leader_id' => $joinLeader->id,
    'status' => 'active',
]);

// Anggota awal: 1 orang (Leader). Karena max_team_size = 2, tersisa tepat 1 slot.
TeamMember::on($db)->create([
    'team_id' => $joinTeam->id,
    'user_id' => $joinLeader->id,
    'role' => 'leader',
    'joined_at' => now(),
]);

// Buat 10 calon anggota terpisah yang berhak bergabung
$candidates = [];
for ($i = 1; $i <= 10; $i++) {
    $candidates[$i] = User::on($db)->create([
        'name' => "Calon Anggota #{$i}",
        'email' => "calon_{$i}_join@ubbg.ac.id",
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
        'rules_accepted_version' => '1',
    ]);
}

$initialMembers = TeamMember::on($db)->where('team_id', $joinTeam->id)->count();
echo "Status Awal Anggota Tim: {$initialMembers} orang (Maksimal: 2, Slot Tersisa: 1)\n";
echo "Meluncurkan 10 calon anggota bergabung serentak via Process::pool...\n\n";

$joinPoolResults = Process::pool(function ($pool) use ($basePath, $joinTeam, $candidates) {
    for ($i = 1; $i <= 10; $i++) {
        $cId = $candidates[$i]->id;
        $cmd = "php artisan --env=testing app:concurrent-join {$joinTeam->invite_code} {$cId} --database=mysql_test";
        $pool->as("join_{$i}")->path($basePath)->command($cmd);
    }
})->wait();

$successJoins = 0;
$rejectedJoins = 0;
echo "Hasil Eksekusi 10 Request Gabung Paralel:\n";
for ($i = 1; $i <= 10; $i++) {
    $res = $joinPoolResults["join_{$i}"];
    $out = trim($res->output());
    $err = trim($res->errorOutput());
    $status = str_contains($out, 'SUCCESS') ? 'SUKSES (MASUK)' : 'GAGAL (DITOLAK)';
    if (str_contains($out, 'SUCCESS')) {
        $successJoins++;
    } else {
        $rejectedJoins++;
    }
    echo sprintf("  [Calon #%02d] Status: %-18s | Output: %s %s\n", $i, $status, $out, $err ? "[Err: $err]" : "");
}

$finalMembers = TeamMember::on($db)->where('team_id', $joinTeam->id)->count();
echo "\nRingkasan Hasil Bagian 2:\n";
echo "  - Total Request Calon: 10\n";
echo "  - Calon Diterima    : {$successJoins}\n";
echo "  - Calon Ditolak     : {$rejectedJoins}\n";
echo "  - Jumlah Anggota Akhir di Database: {$finalMembers}\n";
echo "  - Batas Maksimal Tim: 2\n";

if ($finalMembers === 2 && $successJoins === 1 && $rejectedJoins === 9) {
    echo "  >> VERIFIKASI SLOT TIM: LOLOS (Tepat 1 calon masuk, total anggota di DB = 2)\n\n";
} else {
    echo "  >> VERIFIKASI SLOT TIM: GAGAL (Jumlah anggota di DB: {$finalMembers})\n\n";
    exit(1);
}

echo "========================================================================\n";
echo "   SEMUA PENGUJIAN KONKURENSI MYSQL 100% SUKSES DAN VALID              \n";
echo "========================================================================\n";
exit(0);
