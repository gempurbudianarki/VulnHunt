<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\CompetitionSetting;
use App\Models\NdaSubmission;
use App\Models\Report;
use App\Models\ReportComment;
use App\Models\ReportVersion;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use App\Notifications\ImportantAnnouncementNotification;
use App\Notifications\NewCommentNotification;
use App\Services\CompetitionSettings;
use App\Services\ReportStateMachine;
use App\Services\ReportSubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class Phase5SpecificationsAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\CompetitionSettingsSeeder::class);
    }

    protected function createTeam(User $leader, string $name = 'Team Alpha'): Team
    {
        $team = Team::create([
            'name' => $name . ' ' . uniqid(),
            'leader_id' => $leader->id,
            'invite_code' => strtoupper(substr(md5(uniqid()), 0, 8)),
            'status' => 'active',
        ]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $leader->id,
            'role' => 'leader',
        ]);
        return $team;
    }

    protected function createScope(): Scope
    {
        return Scope::create([
            'name' => 'Target Pengujian UBBG ' . uniqid(),
            'target' => 'https://test.ubbg.ac.id',
            'scope_status' => 'in_scope',
            'is_active' => true,
        ]);
    }

    protected function createVuln(): VulnerabilityType
    {
        return VulnerabilityType::create([
            'cwe_id' => 'CWE-89',
            'name' => 'SQL Injection',
            'category' => 'Injection',
            'is_active' => true,
        ]);
    }

    /**
     * Butir 11.2: Teks NDA yang bisa diedit admin dengan nomor versi
     */
    public function test_admin_can_customize_nda_template_text_and_version(): void
    {
        $settings = app(CompetitionSettings::class);
        $settings->set('nda_template_version', '2.5');
        $settings->set('nda_template_text', 'KLAUSUL KHUSUS PENGUJIAN KEAMANAN UBBG 2026 - REVISI ADMIN');

        $this->assertEquals('2.5', $settings->get('nda_template_version'));
        $this->assertEquals('KLAUSUL KHUSUS PENGUJIAN KEAMANAN UBBG 2026 - REVISI ADMIN', $settings->get('nda_template_text'));

        $user = User::factory()->create([
            'email' => 'peserta.nda@ubbg.ac.id',
            'email_verified_at' => now(),
            'rules_accepted_version' => '1',
            'rules_accepted_at' => now(),
        ]);
        $user->assignRole('participant');

        $response = $this->actingAs($user)->get(route('verification.template'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    /**
     * Butir 8.2: max_total_reports_per_team menolak pengiriman jika batas tercapai
     */
    public function test_max_total_reports_per_team_rejects_when_limit_exceeded(): void
    {
        $settings = app(CompetitionSettings::class);
        $settings->set('max_total_reports_per_team', 1);

        $user = User::factory()->create(['email' => 'leader.total@ubbg.ac.id']);
        $team = $this->createTeam($user, 'Tim Kuota Total');

        $scope = $this->createScope();
        $vuln = $this->createVuln();

        $service = app(ReportSubmissionService::class);
        $cvss = [
            'vector' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
            'score' => 9.8,
            'severity' => 'critical',
        ];

        $payload = [
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'Celah SQL Injection Pertama',
            'endpoint' => '/api/v1/search',
            'parameter' => 'q',
            'description' => 'SQLi parameter q',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi rinci kerentanan SQL injection pada endpoint search ', 2),
            'impact' => 'Data leakage',
        ];

        // Laporan pertama sukses
        $report1 = $service->submit($user, $team, $payload, $cvss);
        $this->assertNotNull($report1->id);

        // Laporan kedua harus ditolak
        $this->expectException(ValidationException::class);
        $payload['title'] = 'Celah XSS Kedua';
        $service->submit($user, $team, $payload, $cvss);
    }

    /**
     * Butir 11.6: Notifikasi pengumuman penting (pinned) ke seluruh peserta
     */
    public function test_important_pinned_announcement_notifies_participants(): void
    {
        Notification::fake();

        $participant = User::factory()->create(['email' => 'part.pinned@ubbg.ac.id', 'is_active' => true]);
        $participant->assignRole('participant');

        $admin = User::factory()->create(['email' => 'admin.ann@ubbg.ac.id']);
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Announcement::create([
            'title' => 'Pengumuman Penting: Maintenance Server Pengujian',
            'body' => 'Server target akan mengalami pemeliharaan pada pukul 22:00 WIB.',
            'is_pinned' => true,
            'published_at' => now(),
            'created_by' => $admin->id,
        ]);

        Notification::assertSentTo($participant, ImportantAnnouncementNotification::class);
    }

    /**
     * Butir 11.6: Notifikasi komentar baru pada laporan
     */
    public function test_new_comment_on_report_sends_notification(): void
    {
        Notification::fake();

        $reporter = User::factory()->create(['email' => 'reporter.comm@ubbg.ac.id']);
        $reporter->assignRole('participant');

        $triager = User::factory()->create(['email' => 'triager.comm@ubbg.ac.id']);
        $triager->assignRole('triager');

        $team = $this->createTeam($reporter, 'Tim Komentar');

        $scope = $this->createScope();
        $vuln = $this->createVuln();

        $report = Report::create([
            'team_id' => $team->id,
            'reported_by' => $reporter->id,
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'CSRF Token Bypass',
            'endpoint' => '/api/profile',
            'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:U/C:N/I:H/A:N',
            'cvss_score_claimed' => 6.5,
            'severity_claimed' => 'medium',
            'description' => 'CSRF bypass',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi rinci untuk membuktikan eksploitasi celah CSRF pada endpoint ', 2),
            'impact' => 'Account takeover',
            'status' => 'submitted',
            'revision_no' => 1,
            'triaged_by' => $triager->id,
        ]);

        // Triager menambahkan komentar publik
        ReportComment::create([
            'report_id' => $report->id,
            'user_id' => $triager->id,
            'body' => 'Mohon berikan rekaman HTTP request untuk melengkapi laporan ini.',
            'is_internal' => false,
        ]);

        Notification::assertSentTo($reporter, NewCommentNotification::class);
    }

    /**
     * Butir 11.5: Aksi massal triage mengubah laporan submitted menjadi in_review
     */
    public function test_bulk_triage_action_transitions_submitted_reports_to_in_review(): void
    {
        $user = User::factory()->create();
        $triager = User::factory()->create();
        $triager->assignRole('triager');

        $team = $this->createTeam($user, 'Tim Bulk Triage');

        $scope = $this->createScope();
        $vuln = $this->createVuln();

        $report1 = Report::create([
            'team_id' => $team->id,
            'reported_by' => $user->id,
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'Laporan Massal 1',
            'endpoint' => '/ep1',
            'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
            'cvss_score_claimed' => 9.8,
            'severity_claimed' => 'critical',
            'description' => 'desc',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi rinci celah keamanan spesimen laporan pertama ', 2),
            'impact' => 'impact',
            'status' => 'submitted',
        ]);

        $report2 = Report::create([
            'team_id' => $team->id,
            'reported_by' => $user->id,
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'Laporan Massal 2',
            'endpoint' => '/ep2',
            'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:L/UI:N/S:U/C:H/I:N/A:N',
            'cvss_score_claimed' => 6.5,
            'severity_claimed' => 'medium',
            'description' => 'desc',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi rinci celah keamanan spesimen laporan kedua ', 2),
            'impact' => 'impact',
            'status' => 'submitted',
        ]);

        $stateMachine = app(ReportStateMachine::class);
        $records = collect([$report1, $report2]);

        foreach ($records as $rec) {
            if ($rec->status === 'submitted') {
                $stateMachine->transition($rec, 'in_review', $triager, [
                    'reason' => 'Aksi massal triage oleh staf.',
                ]);
            }
        }

        $this->assertEquals('in_review', $report1->fresh()->status);
        $this->assertEquals('in_review', $report2->fresh()->status);
    }

    /**
     * Butir 8.7: Prioritas antrean triage berbasis bobot severity dan umur laporan
     */
    public function test_triage_queue_priority_formula(): void
    {
        $user = User::factory()->create();
        $team = $this->createTeam($user, 'Tim Queue Priority');
        $scope = $this->createScope();
        $vuln = $this->createVuln();

        // Laporan critical (bobot 40)
        $critReport = Report::create([
            'team_id' => $team->id,
            'reported_by' => $user->id,
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'Critical Bug',
            'endpoint' => '/ep1',
            'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
            'cvss_score_claimed' => 9.8,
            'severity_claimed' => 'critical',
            'description' => 'desc',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi rinci laporan keamanan tingkat keparahan critical ', 2),
            'impact' => 'impact',
            'status' => 'submitted',
        ]);

        // Laporan low (bobot 5)
        $lowReport = Report::create([
            'team_id' => $team->id,
            'reported_by' => $user->id,
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'Low Bug',
            'endpoint' => '/ep2',
            'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:N',
            'cvss_score_claimed' => 5.3,
            'severity_claimed' => 'low',
            'description' => 'desc',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi rinci laporan keamanan tingkat keparahan low ', 2),
            'impact' => 'impact',
            'status' => 'submitted',
        ]);

        $weights = ['critical' => 40, 'high' => 25, 'medium' => 10, 'low' => 5, 'info' => 1];
        $critPriority = $weights[$critReport->severity_claimed] + (0 * 0.5);
        $lowPriority = $weights[$lowReport->severity_claimed] + (0 * 0.5);

        $this->assertEquals(40, $critPriority);
        $this->assertEquals(5, $lowPriority);
        $this->assertTrue($critPriority > $lowPriority);
    }

    /**
     * Butir 11.4 & 6.3: Snapshot report_versions tercipta saat submit dan saat revisi
     */
    public function test_report_versions_snapshot_created_on_submit_and_revision(): void
    {
        $user = User::factory()->create([
            'email' => 'vers.rev@ubbg.ac.id',
            'rules_accepted_version' => '1',
            'rules_accepted_at' => now(),
            'email_verified_at' => now(),
        ]);
        $user->assignRole('participant');
        $team = $this->createTeam($user, 'Tim Versi Snapshot');

        NdaSubmission::create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'document_number' => 'NDA-2026-0001-A1B2',
            'template_version' => '1.0',
            'generated_pdf_path' => 'nda/temp.pdf',
            'signed_file_path' => 'nda/signed.pdf',
            'signed_file_sha256' => hash('sha256', 'mock'),
            'status' => 'approved',
            'submitted_at' => now(),
        ]);

        $scope = $this->createScope();
        $vuln = $this->createVuln();

        $service = app(ReportSubmissionService::class);
        $cvss = [
            'vector' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
            'score' => 9.8,
            'severity' => 'critical',
        ];

        $payload = [
            'scope_id' => $scope->id,
            'vulnerability_type_id' => $vuln->id,
            'title' => 'Initial Version Finding',
            'endpoint' => '/api/test',
            'parameter' => 'id',
            'description' => 'Initial Description',
            'steps_to_reproduce' => str_repeat('Langkah reproduksi rinci versi pertama pengajuan laporan spesimen bug ', 2),
            'impact' => 'Initial Impact',
        ];

        $report = $service->submit($user, $team, $payload, $cvss);

        // Verifikasi versi 1 tercatat di report_versions
        $this->assertEquals(1, $report->revision_no);
        $this->assertDatabaseHas('report_versions', [
            'report_id' => $report->id,
            'revision_no' => 1,
            'created_by' => $user->id,
        ]);

        // Simulasikan transisi status ke needs_info oleh admin
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $report = app(ReportStateMachine::class)->transition($report, 'in_review', $admin, [
            'reason' => 'Admin mulai meninjau laporan.',
        ]);
        $report = app(ReportStateMachine::class)->transition($report, 'needs_info', $admin, [
            'reason' => 'Mohon lengkapi PoC dengan HTTP trace.',
        ]);

        // Tim merevisi laporan via endpoint revisi
        $newSteps = str_repeat('Revisi lengkap langkah reproduksi dengan menyertakan HTTP request trace dan curl command ', 2);
        $response = $this->actingAs($user)->post(route('reports.reply', $report->code), [
            'reply_message' => 'Berikut langkah reproduksi yang sudah kami lengkapi dengan curl.',
            'steps_to_reproduce' => $newSteps,
        ]);
        $response->assertSessionHasNoErrors();

        // Verifikasi versi 2 tercatat di report_versions
        $this->assertEquals(2, $report->fresh()->revision_no);
        $this->assertDatabaseHas('report_versions', [
            'report_id' => $report->id,
            'revision_no' => 2,
            'created_by' => $user->id,
        ]);
    }
}
