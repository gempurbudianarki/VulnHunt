<?php

namespace Tests\Feature;

use App\Models\NdaSubmission;
use App\Models\Report;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Services\CompetitionSettings;
use App\Services\LeaderboardService;
use App\Services\ReportStateMachine;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LeaderboardAggregationAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected CompetitionSettings $settings;
    protected LeaderboardService $leaderboardService;
    protected User $admin;
    protected User $participant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->settings = app(CompetitionSettings::class);
        $this->leaderboardService = app(LeaderboardService::class);

        $this->admin = User::factory()->create([
            'email' => 'admin.lb@ubbg.ac.id',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
            'is_active' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->participant = User::factory()->create([
            'name' => 'Peserta Utama',
            'nim' => 'NIM11111111',
            'email' => 'peserta.lb@ubbg.ac.id',
            'email_verified_at' => now(),
            'rules_accepted_version' => '1',
            'rules_accepted_at' => now(),
            'is_active' => true,
        ]);
        $this->participant->assignRole('participant');
    }

    public function test_tie_break_4_tingkat_dan_penyaringan_tim_diskualifikasi(): void
    {
        $scope = Scope::create([
            'name' => 'Target Leaderboard',
            'target' => 'https://lb.ubbg.ac.id',
            'asset_type' => 'web',
            'scope_status' => 'in_scope',
            'criticality_weight' => 1.0,
            'is_active' => true,
        ]);

        $vuln = VulnerabilityType::create([
            'name' => 'SQL Injection',
            'cwe_id' => 'CWE-89',
            'base_score' => 8.0,
            'is_active' => true,
        ]);

        // Helper untuk membuat tim dan anggota
        $createTeam = function (string $name, string $status = 'active') {
            $user = User::factory()->create(['name' => "User {$name}", 'nim' => "NIM_{$name}"]);
            $team = Team::create([
                'name' => $name,
                'slug' => strtolower(str_replace(' ', '-', $name)),
                'invite_code' => strtoupper(substr(md5($name), 0, 8)),
                'leader_id' => $user->id,
                'status' => $status,
            ]);
            TeamMember::create([
                'team_id' => $team->id,
                'user_id' => $user->id,
                'role' => 'leader',
                'joined_at' => now(),
            ]);
            return $team;
        };

        // Helper untuk membuat laporan valid
        $createValidReport = function (Team $team, string $severity, int $points, string $closedAt) use ($scope, $vuln) {
            return Report::create([
                'code' => 'VH-'.strtoupper(substr(md5(uniqid()), 0, 8)),
                'team_id' => $team->id,
                'reported_by' => $team->leader_id,
                'scope_id' => $scope->id,
                'vulnerability_type_id' => $vuln->id,
                'title' => 'Laporan Uji '.$team->name,
                'endpoint' => '/api',
                'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
                'cvss_score_claimed' => 9.8,
                'severity_claimed' => $severity,
                'cvss_vector_final' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
                'cvss_score_final' => 9.8,
                'severity_final' => $severity,
                'description' => 'Deskripsi temuan',
                'steps_to_reproduce' => str_repeat('Langkah reproduksi pengujian keamanan celah. ', 4),
                'impact' => 'Dampak keamanan',
                'status' => 'valid',
                'points_awarded' => $points,
                'submitted_at' => Carbon::parse($closedAt)->subMinutes(30),
                'closed_at' => Carbon::parse($closedAt),
            ]);
        };

        // 1. Tim Diskualifikasi (Punya 1000 poin tapi status disqualified -> TIDAK BOLEH MUNCUL)
        $teamDisqualified = $createTeam('Tim Curang Diskualifikasi', 'disqualified');
        $createValidReport($teamDisqualified, 'critical', 1000, '2026-10-25 10:00:00');

        // 2. Tingkat 1: Total Poin tertinggi menang
        $teamAlpha = $createTeam('Tim Alpha Juara'); // 300 poin
        $createValidReport($teamAlpha, 'critical', 300, '2026-10-25 12:00:00');

        // 3. Tingkat 2: Poin sama (200 poin), jumlah critical lebih banyak menang
        $teamBeta1 = $createTeam('Tim Beta 2 Critical'); // 200 poin, 2 critical
        $createValidReport($teamBeta1, 'critical', 100, '2026-10-25 11:00:00');
        $createValidReport($teamBeta1, 'critical', 100, '2026-10-25 13:00:00');

        $teamBeta2 = $createTeam('Tim Beta 1 Critical'); // 200 poin, 1 critical, 2 high (50x2)
        $createValidReport($teamBeta2, 'critical', 100, '2026-10-25 11:00:00');
        $createValidReport($teamBeta2, 'high', 50, '2026-10-25 12:00:00');
        $createValidReport($teamBeta2, 'high', 50, '2026-10-25 12:30:00');

        // 4. Tingkat 3: Poin sama (100 poin), critical sama (0), jumlah high lebih banyak menang
        $teamGamma1 = $createTeam('Tim Gamma 2 High'); // 100 poin, 0 critical, 2 high
        $createValidReport($teamGamma1, 'high', 50, '2026-10-25 11:00:00');
        $createValidReport($teamGamma1, 'high', 50, '2026-10-25 12:00:00');

        $teamGamma2 = $createTeam('Tim Gamma 1 High'); // 100 poin, 0 critical, 1 high, 2 medium
        $createValidReport($teamGamma2, 'high', 50, '2026-10-25 11:00:00');
        $createValidReport($teamGamma2, 'medium', 25, '2026-10-25 11:30:00');
        $createValidReport($teamGamma2, 'medium', 25, '2026-10-25 12:00:00');

        // 5. Tingkat 4: Poin sama (60 poin), critical sama (0), high sama (1), validasi terakhir paling awal menang
        $teamDeltaEarly = $createTeam('Tim Delta Validasi Awal'); // Validasi terakhir jam 14:00
        $createValidReport($teamDeltaEarly, 'high', 60, '2026-10-25 14:00:00');

        $teamDeltaLate = $createTeam('Tim Delta Validasi Akhir'); // Validasi terakhir jam 15:00
        $createValidReport($teamDeltaLate, 'high', 60, '2026-10-25 15:00:00');

        // Eksekusi query leaderboard
        $rankings = $this->leaderboardService->queryAggregatedRankings();

        // Verifikasi tim diskualifikasi tidak masuk
        $this->assertFalse($rankings->contains('team_name', 'Tim Curang Diskualifikasi'));

        // Verifikasi urutan presisi
        $teamNames = $rankings->pluck('team_name')->toArray();

        // 1: Tim Alpha Juara (300 poin)
        $this->assertEquals('Tim Alpha Juara', $teamNames[0]);

        // 2: Tim Beta 2 Critical (200 poin, 2 critical) sebelum Tim Beta 1 Critical (200 poin, 1 critical)
        $this->assertEquals('Tim Beta 2 Critical', $teamNames[1]);
        $this->assertEquals('Tim Beta 1 Critical', $teamNames[2]);

        // 3: Tim Gamma 2 High (100 poin, 2 high) sebelum Tim Gamma 1 High (100 poin, 1 high)
        $this->assertEquals('Tim Gamma 2 High', $teamNames[3]);
        $this->assertEquals('Tim Gamma 1 High', $teamNames[4]);

        // 4: Tim Delta Validasi Awal (14:00) sebelum Tim Delta Validasi Akhir (15:00)
        $this->assertEquals('Tim Delta Validasi Awal', $teamNames[5]);
        $this->assertEquals('Tim Delta Validasi Akhir', $teamNames[6]);
    }

    public function test_tampilan_hanya_nama_tim_poin_dan_laporan_valid_tanpa_nama_anggota_atau_nim(): void
    {
        $team = Team::create([
            'name' => 'Tim Cyber UBBG',
            'slug' => 'tim-cyber-ubbg',
            'invite_code' => 'CYBER001',
            'leader_id' => $this->participant->id,
            'status' => 'active',
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->participant->id,
            'role' => 'leader',
            'joined_at' => now(),
        ]);

        $secondMember = User::factory()->create([
            'name' => 'Anggota Kedua Rahasia',
            'nim' => 'NIM_RAHASIA_777',
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $secondMember->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->settings->set('leaderboard_mode', 'visible');

        $response = $this->actingAs($this->participant)->get(route('leaderboard.index'));

        $response->assertStatus(200);
        $response->assertSee('Tim Cyber UBBG');
        $response->assertSee('Total Poin');
        $response->assertSee('Laporan Valid');

        // STRICT PRIVACY PROTECTION: Nama anggota & NIM DILARANG tampil di tabel papan peringkat
        $response->assertDontSee('Anggota Kedua Rahasia');
        $response->assertDontSee('NIM_RAHASIA_777');
        $response->assertDontSee('orang'); // Tidak ada kolom anggota 'X orang'
    }

    public function test_mode_hidden_dan_hidden_until_end_tidak_membocorkan_data_ke_peserta_tetapi_admin_bisa_melihat(): void
    {
        $team = Team::create([
            'name' => 'Tim Rahasia Klasemen',
            'slug' => 'tim-rahasia-klasemen',
            'invite_code' => 'SECRET01',
            'leader_id' => $this->participant->id,
            'status' => 'active',
        ]);

        // 1. Mode hidden mutlak
        $this->settings->set('leaderboard_mode', 'hidden');

        // Peserta biasa
        $respHidden = $this->actingAs($this->participant)->get(route('leaderboard.index'));
        $respHidden->assertStatus(200);
        $respHidden->assertSee('Papan Peringkat Sedang Dibekukan');
        $respHidden->assertDontSee('Tim Rahasia Klasemen');

        // Request JSON oleh peserta pada mode hidden harus 403 dan data kosong
        $respJson = $this->actingAs($this->participant)->getJson(route('leaderboard.index'));
        $respJson->assertStatus(403);
        $respJson->assertJsonFragment(['data' => []]);

        // Admin tetap bisa melihat
        $respAdmin = $this->actingAs($this->admin)->get(route('leaderboard.index'));
        $respAdmin->assertStatus(200);
        $respAdmin->assertSee('Tim Rahasia Klasemen');
        $respAdmin->assertSee('Pratinjau Admin');

        // 2. Mode hidden_until_end dengan submission_close_at null -> tidak pernah tampil otomatis
        $this->settings->set('leaderboard_mode', 'hidden_until_end');
        $this->settings->set('submission_close_at', null);

        $respNullEnd = $this->actingAs($this->participant)->get(route('leaderboard.index'));
        $respNullEnd->assertSee('Papan Peringkat Sedang Dibekukan');
        $respNullEnd->assertDontSee('Tim Rahasia Klasemen');

        // 3. Mode hidden_until_end dengan tanggal batas (submission_close_at)
        $this->settings->set('submission_close_at', '2026-10-25 18:00:00'); // WIB = 11:00 UTC

        // Sebelum waktu batas (17:59:59 WIB) -> Tersembunyi
        Carbon::setTestNow(Carbon::parse('2026-10-25 17:59:59', 'Asia/Jakarta'));
        $respBefore = $this->actingAs($this->participant)->get(route('leaderboard.index'));
        $respBefore->assertSee('Papan Peringkat Sedang Dibekukan');
        $respBefore->assertDontSee('Tim Rahasia Klasemen');

        // Setelah waktu batas lewat (18:00:01 WIB) -> Otomatis Terbuka!
        Carbon::setTestNow(Carbon::parse('2026-10-25 18:00:01', 'Asia/Jakarta'));
        $respAfter = $this->actingAs($this->participant)->get(route('leaderboard.index'));
        $respAfter->assertOk();
        $respAfter->assertSee('Tim Rahasia Klasemen');
        $respAfter->assertDontSee('Papan Peringkat Sedang Dibekukan');

        Carbon::setTestNow();
    }

    public function test_perubahan_status_valid_mengubah_peringkat_seketika_tanpa_menunggu_ttl(): void
    {
        $this->settings->set('leaderboard_mode', 'visible');

        $teamA = Team::create([
            'name' => 'Tim Dynamic A',
            'slug' => 'tim-dynamic-a',
            'invite_code' => 'DYNA0001',
            'leader_id' => $this->participant->id,
            'status' => 'active',
        ]);

        $userB = User::factory()->create();
        $teamB = Team::create([
            'name' => 'Tim Dynamic B',
            'slug' => 'tim-dynamic-b',
            'invite_code' => 'DYNA0002',
            'leader_id' => $userB->id,
            'status' => 'active',
        ]);

        $scope = Scope::create([
            'name' => 'Scope Dyn',
            'target' => 'https://dyn.ubbg.ac.id',
            'asset_type' => 'web',
            'scope_status' => 'in_scope',
            'criticality_weight' => 1.0,
            'is_active' => true,
        ]);

        $vuln = VulnerabilityType::create([
            'name' => 'IDOR Vulnerability',
            'cwe_id' => 'CWE-639',
            'base_score' => 6.5,
            'is_active' => true,
        ]);

        // Awalnya: Tim A punya 50 poin, Tim B 0 poin -> Peringkat #1 adalah Tim A
        Report::create([
            'code' => 'VH-DYNA01',
            'team_id' => $teamA->id,
            'reported_by' => $this->participant->id,
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'Temuan Tim A',
            'endpoint' => '/api/v1/user',
            'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:L/A:N',
            'cvss_score_claimed' => 6.5,
            'severity_claimed' => 'medium',
            'severity_final' => 'medium',
            'description' => 'IDOR deskripsi',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi pengujian keamanan IDOR. ', 4),
            'impact' => 'Akses data user lain',
            'status' => 'valid',
            'points_awarded' => 50,
            'submitted_at' => now(),
            'closed_at' => now(),
        ]);

        // Panggil getRankings sehingga tersimpan di Cache (TTL 1 jam)
        $initialRankings = $this->leaderboardService->getRankings();
        $this->assertEquals('Tim Dynamic A', $initialRankings[0]->team_name);
        $this->assertEquals(50, $initialRankings[0]->total_points);

        // Buat laporan untuk Tim B dalam status in_review
        $reportB = Report::create([
            'code' => 'VH-DYNB01',
            'team_id' => $teamB->id,
            'reported_by' => $userB->id,
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'Temuan Critical Tim B',
            'endpoint' => '/api/v1/admin',
            'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
            'cvss_score_claimed' => 9.8,
            'severity_claimed' => 'critical',
            'description' => 'RCE deskripsi',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi pengujian keamanan RCE. ', 4),
            'impact' => 'Ambil alih server kampus',
            'status' => 'in_review',
            'points_awarded' => 0,
            'submitted_at' => now(),
        ]);

        // Juri memvalidasi laporan Tim B menggunakan ReportStateMachine
        $stateMachine = app(ReportStateMachine::class);
        $stateMachine->transition($reportB, 'valid', $this->admin, [
            'cvss_vector_final' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
            'quality_factor' => 1.0,
        ]);

        // SEKETIKA TANPA MENUNGGU TTL CACHE: Peringkat #1 HARUS BERUBAH MENJADI TIM B (100 poin)!
        $updatedRankings = $this->leaderboardService->getRankings();
        $this->assertEquals('Tim Dynamic B', $updatedRankings[0]->team_name);
        $this->assertEquals(100, $updatedRankings[0]->total_points);
        $this->assertEquals('Tim Dynamic A', $updatedRankings[1]->team_name);
    }
}
