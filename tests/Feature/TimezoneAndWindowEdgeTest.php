<?php

namespace Tests\Feature;

use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Services\CompetitionSettings;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TimezoneAndWindowEdgeTest extends TestCase
{
    use RefreshDatabase;

    protected CompetitionSettings $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('private');
        $this->settings = app(CompetitionSettings::class);
    }

    public function test_input_tanggal_wib_disimpan_dalam_utc_dan_dibaca_kembali_dalam_wib(): void
    {
        // Admin memasukkan waktu dalam zona WIB (Asia/Jakarta)
        $wibInput = '2026-10-25 09:00:00';
        $this->settings->set('registration_open_at', $wibInput);

        // Di database/storage mentah harus tersimpan sebagai UTC (02:00:00)
        $rawUtc = $this->settings->get('registration_open_at');
        $this->assertEquals('2026-10-25 02:00:00', $rawUtc);

        // Helper getWib harus mengembalikan Carbon pada zona Asia/Jakarta
        $wibCarbon = $this->settings->getWib('registration_open_at');
        $this->assertNotNull($wibCarbon);
        $this->assertEquals('Asia/Jakarta', $wibCarbon->timezoneName);
        $this->assertEquals('2026-10-25 09:00:00', $wibCarbon->format('Y-m-d H:i:s'));
    }

    public function test_batas_jendela_pendaftaran_pada_tepi_menit_wib_via_http_request(): void
    {
        // Set jendela pendaftaran 09:00:00 WIB s/d 17:00:00 WIB
        $this->settings->set('registration_open_at', '2026-10-25 09:00:00');
        $this->settings->set('registration_close_at', '2026-10-25 17:00:00');
        $this->settings->set('allowed_email_domains', ['ubbg.ac.id']);

        $regPayload = [
            'name' => 'Mhs Baru',
            'nim' => '20269999',
            'email' => 'mhs.baru@ubbg.ac.id',
            'phone' => '081299999999',
            'study_program' => 'Informatika',
            'cohort_year' => 2024,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'accept_rules' => '1',
        ];

        // 1. Tepat 1 detik sebelum buka: 08:59:59 WIB (01:59:59 UTC) -> DITOLAK
        Carbon::setTestNow(Carbon::parse('2026-10-25 08:59:59', 'Asia/Jakarta'));
        $respEarly = $this->post('/register', $regPayload);
        $respEarly->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', ['email' => 'mhs.baru@ubbg.ac.id']);

        // 2. Tepat pada detik awal buka: 09:00:00 WIB (02:00:00 UTC) -> DITERIMA
        Carbon::setTestNow(Carbon::parse('2026-10-25 09:00:00', 'Asia/Jakarta'));
        $respOpen = $this->post('/register', $regPayload);
        $respOpen->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'mhs.baru@ubbg.ac.id']);

        // Logout pengguna pertama agar request berikutnya tidak dianggap guest terautentikasi
        auth()->logout();

        // 3. Tepat pada detik akhir tutup: 17:00:00 WIB (10:00:00 UTC) -> DITERIMA (inklusif tepi menit)
        Carbon::setTestNow(Carbon::parse('2026-10-25 17:00:00', 'Asia/Jakarta'));
        $regPayload2 = array_merge($regPayload, [
            'nim' => '20269998',
            'email' => 'mhs.tepi@ubbg.ac.id',
        ]);
        $respEdge = $this->post('/register', $regPayload2);
        $respEdge->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'mhs.tepi@ubbg.ac.id']);

        auth()->logout();

        // 4. Lewat 1 detik setelah tutup: 17:00:01 WIB (10:00:01 UTC) -> DITOLAK
        Carbon::setTestNow(Carbon::parse('2026-10-25 17:00:01', 'Asia/Jakarta'));
        $regPayload3 = array_merge($regPayload, [
            'nim' => '20269997',
            'email' => 'mhs.terlambat@ubbg.ac.id',
        ]);
        $respLate = $this->post('/register', $regPayload3);
        $respLate->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', ['email' => 'mhs.terlambat@ubbg.ac.id']);

        Carbon::setTestNow();
    }

    public function test_batas_jendela_pengiriman_laporan_pada_tepi_menit_wib_via_http_request(): void
    {
        // Set jendela pengiriman 10:00:00 WIB s/d 18:00:00 WIB
        $this->settings->set('submission_open_at', '2026-10-25 10:00:00');
        $this->settings->set('submission_close_at', '2026-10-25 18:00:00');

        $user = User::factory()->create([
            'email' => 'leader.edge@ubbg.ac.id',
            'email_verified_at' => now(),
            'rules_accepted_version' => '1',
            'rules_accepted_at' => now(),
            'is_active' => true,
        ]);
        $user->assignRole('participant');

        $team = Team::create([
            'name' => 'Tim Tepi Menit',
            'slug' => 'tim-tepi-menit',
            'invite_code' => 'TEPI01',
            'leader_id' => $user->id,
            'status' => 'active',
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => 'leader',
            'joined_at' => now(),
        ]);

        \App\Models\NdaSubmission::create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'document_number' => 'NDA-EDGE-001',
            'template_version' => '1.0',
            'signed_file_path' => 'nda/test.pdf',
            'signed_file_sha256' => hash('sha256', 'test'),
            'status' => 'approved',
            'reviewed_at' => now(),
            'submitted_at' => now(),
        ]);

        $scope = Scope::create([
            'name' => 'Portal Kampus Edge',
            'target' => 'https://edge.ubbg.ac.id',
            'asset_type' => 'web',
            'scope_status' => 'in_scope',
            'criticality_weight' => 1.0,
            'is_active' => true,
        ]);

        $vuln = VulnerabilityType::create([
            'name' => 'Cross-Site Scripting (Reflected)',
            'cwe_id' => 'CWE-79',
            'base_score' => 6.1,
            'is_active' => true,
        ]);

        // Buat file lampiran yang sah
        $file = UploadedFile::fake()->image('proof.png', 400, 300);

        $reportPayload = [
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'endpoint' => '/search',
            'parameter' => 'q',
            'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N',
            'title' => 'Reflected XSS on Search Parameter',
            'description' => 'Ditemukan celah reflected XSS pada parameter pencarian portal kampus.',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi pengujian keamanan celah XSS pada endpoint search. ', 3),
            'impact' => 'Pencurian session cookie pengguna lain.',
            'remediation' => 'Gunakan htmlspecialchars pada output parameter.',
            'agree_no_privacy_violation' => '1',
            'agree_no_destruction' => '1',
            'agree_in_scope_only' => '1',
            'attachments' => [$file],
        ];

        // 1. Tepat 1 detik sebelum buka: 09:59:59 WIB (02:59:59 UTC) -> DITOLAK
        Carbon::setTestNow(Carbon::parse('2026-10-25 09:59:59', 'Asia/Jakarta'));
        $respEarly = $this->actingAs($user)->post(route('reports.store'), $reportPayload);
        $respEarly->assertSessionHasErrors();
        $this->assertDatabaseCount('reports', 0);

        // 2. Tepat pada detik buka: 10:00:00 WIB (03:00:00 UTC) -> DITERIMA
        Carbon::setTestNow(Carbon::parse('2026-10-25 10:00:00', 'Asia/Jakarta'));
        $respOpen = $this->actingAs($user)->post(route('reports.store'), $reportPayload);
        $respOpen->assertSessionHas('success');
        $this->assertDatabaseCount('reports', 1);

        // 3. Tepat pada detik akhir tutup: 18:00:00 WIB (11:00:00 UTC) -> DITERIMA
        Carbon::setTestNow(Carbon::parse('2026-10-25 18:00:00', 'Asia/Jakarta'));
        $file2 = UploadedFile::fake()->image('proof2.png', 400, 300);
        $reportPayload['attachments'] = [$file2];
        $respEdge = $this->actingAs($user)->post(route('reports.store'), $reportPayload);
        $respEdge->assertSessionHas('success');
        $this->assertDatabaseCount('reports', 2);

        // 4. Lewat 1 detik setelah tutup: 18:00:01 WIB (11:00:01 UTC) -> DITOLAK
        Carbon::setTestNow(Carbon::parse('2026-10-25 18:00:01', 'Asia/Jakarta'));
        $file3 = UploadedFile::fake()->image('proof3.png', 400, 300);
        $reportPayload['attachments'] = [$file3];
        $respLate = $this->actingAs($user)->post(route('reports.store'), $reportPayload);
        $respLate->assertSessionHasErrors();
        $this->assertDatabaseCount('reports', 2);

        Carbon::setTestNow();
    }
}
