<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Pastikan nilai submission_close_at terisi dalam format WIB murni
DB::table('competition_settings')->updateOrInsert(
    ['key' => 'submission_close_at'],
    ['value' => json_encode(['value' => '2026-10-25 18:00:00']), 'updated_at' => now()]
);

// Jika tabel teams kosong, masukkan 1 tim contoh
if (! DB::table('teams')->exists()) {
    $userId = DB::table('users')->value('id') ?? DB::table('users')->insertGetId([
        'name' => 'Demo User',
        'email' => 'demo@ubbg.ac.id',
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('teams')->insert([
        'name' => 'Tim Demo WIB',
        'slug' => 'tim-demo-wib',
        'invite_code' => 'DEMOWIB1',
        'leader_id' => $userId,
        'status' => 'active',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Jika tabel announcements kosong, masukkan 1 pengumuman contoh
if (! DB::table('announcements')->exists()) {
    DB::table('announcements')->insert([
        'title' => 'Pengumuman Resmi Jadwal Lomba',
        'body' => 'Pengumuman resmi dalam format zona waktu WIB.',
        'is_pinned' => true,
        'published_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// Jika tabel reports kosong, masukkan 1 laporan contoh
if (! DB::table('reports')->exists()) {
    $teamId = DB::table('teams')->value('id');
    $userId = DB::table('users')->value('id');
    $scopeId = DB::table('scopes')->value('id') ?? DB::table('scopes')->insertGetId([
        'name' => 'Portal Demo',
        'target' => 'https://demo.ubbg.ac.id',
        'scope_status' => 'in_scope',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $vulnId = DB::table('vulnerability_types')->value('id') ?? DB::table('vulnerability_types')->insertGetId([
        'cwe_id' => 'CWE-79',
        'name' => 'XSS',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('reports')->insert([
        'team_id' => $teamId,
        'reported_by' => $userId,
        'scope_id' => $scopeId,
        'vulnerability_type_id' => $vulnId,
        'title' => 'Temuan Validasi Zona Waktu',
        'endpoint' => '/demo',
        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
        'cvss_score_claimed' => 9.8,
        'severity_claimed' => 'critical',
        'description' => 'Deskripsi temuan verifikasi WIB',
        'steps_to_reproduce' => 'Langkah reproduksi',
        'impact' => 'Dampak',
        'status' => 'submitted',
        'submitted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

echo "=== RAW SELECT QUERIES DARI DATABASE (ZONA WAKTU ASIA/JAKARTA - WIB) ===\n\n";

// 1. SELECT mentah reports.submitted_at
echo "1. Query: SELECT id, title, submitted_at FROM reports LIMIT 1;\n";
$reports = DB::select("SELECT id, title, submitted_at FROM reports LIMIT 1");
foreach ($reports as $r) {
    echo "   [reports] ID: {$r->id} | Title: {$r->title} | submitted_at: {$r->submitted_at}\n";
}
echo "\n";

// 2. SELECT mentah announcements.published_at
echo "2. Query: SELECT id, title, published_at FROM announcements LIMIT 1;\n";
$announcements = DB::select("SELECT id, title, published_at FROM announcements LIMIT 1");
foreach ($announcements as $a) {
    echo "   [announcements] ID: {$a->id} | Title: {$a->title} | published_at: {$a->published_at}\n";
}
echo "\n";

// 3. SELECT mentah nilai submission_close_at di competition_settings
echo "3. Query: SELECT `key`, `value` FROM competition_settings WHERE `key` = 'submission_close_at';\n";
$settings = DB::select("SELECT `key`, `value` FROM competition_settings WHERE `key` = 'submission_close_at'");
foreach ($settings as $s) {
    echo "   [competition_settings] Key: {$s->key} | Value: {$s->value}\n";
}
echo "\n";

// 4. SELECT mentah teams.created_at
echo "4. Query: SELECT id, name, created_at FROM teams LIMIT 1;\n";
$teams = DB::select("SELECT id, name, created_at FROM teams LIMIT 1");
foreach ($teams as $t) {
    echo "   [teams] ID: {$t->id} | Name: {$t->name} | created_at: {$t->created_at}\n";
}
echo "\n";

// 5. Cek @@session.time_zone dan @@global.time_zone di MySQL
$tz = DB::select("SELECT @@session.time_zone as session_tz, @@global.time_zone as global_tz, NOW() as db_now");
foreach ($tz as $row) {
    echo "   [mysql_connection] session.time_zone: {$row->session_tz} | NOW(): {$row->db_now}\n";
}
