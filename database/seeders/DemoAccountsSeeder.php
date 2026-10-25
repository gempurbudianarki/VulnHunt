<?php

namespace Database\Seeders;

use App\Models\NdaSubmission;
use App\Models\Report;
use App\Models\ReportComment;
use App\Models\Scope;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\VulnerabilityType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Admin Demo (Langsung Siap Pakai 2FA dengan Recovery Code & Secret Standar)
        $admin = User::updateOrCreate(
            ['email' => 'admin.demo@ubbg.ac.id'],
            [
                'name' => 'Admin Utama (Demo)',
                'password' => Hash::make('AdminUBBG2026!'),
                'must_change_password' => false,
                'email_verified_at' => now(),
                'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
                'two_factor_recovery_codes' => encrypt(json_encode([
                    '11111111-22222222',
                    '33333333-44444444',
                    '55555555-66666666',
                    '77777777-88888888',
                ])),
                'two_factor_confirmed_at' => now(),
                'rules_accepted_version' => '1',
                'rules_accepted_at' => now(),
                'rules_accepted_ip' => '127.0.0.1',
                'is_active' => true,
            ]
        );
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // 2. Akun Triager Demo (Siap Pakai untuk Uji Akses Triager di /admin)
        $triager = User::updateOrCreate(
            ['email' => 'triager.demo@ubbg.ac.id'],
            [
                'name' => 'Triager Panitia (Demo)',
                'password' => Hash::make('TriagerUBBG2026!'),
                'must_change_password' => false,
                'email_verified_at' => now(),
                'two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'),
                'two_factor_recovery_codes' => encrypt(json_encode([
                    '11111111-22222222',
                    '33333333-44444444',
                ])),
                'two_factor_confirmed_at' => now(),
                'rules_accepted_version' => '1',
                'rules_accepted_at' => now(),
                'rules_accepted_ip' => '127.0.0.1',
                'is_active' => true,
            ]
        );
        if (! $triager->hasRole('triager')) {
            $triager->assignRole('triager');
        }

        // 3. Akun Peserta Demo (Approved Penuh: Siap Akses /app, Scope, & Laporan)
        $peserta = User::updateOrCreate(
            ['email' => 'peserta@ubbg.ac.id'],
            [
                'name' => 'Fulan Mahasiswa (Peserta)',
                'nim' => '20260001',
                'phone' => '081234567890',
                'study_program' => 'Informatika',
                'cohort_year' => 2023,
                'password' => Hash::make('Password123!'),
                'must_change_password' => false,
                'email_verified_at' => now(),
                'rules_accepted_version' => '1',
                'rules_accepted_at' => now(),
                'rules_accepted_ip' => '127.0.0.1',
                'is_active' => true,
            ]
        );
        if (! $peserta->hasRole('participant')) {
            $peserta->assignRole('participant');
        }

        // 4. Buat Tim Peserta
        $team = Team::firstOrCreate(
            ['name' => 'Tim Garuda Cyber'],
            [
                'slug' => 'tim-garuda-cyber',
                'invite_code' => 'GARUDA01',
                'leader_id' => $peserta->id,
                'status' => 'active',
            ]
        );

        TeamMember::firstOrCreate(
            ['team_id' => $team->id, 'user_id' => $peserta->id],
            ['role' => 'leader', 'joined_at' => now()]
        );

        // 5. Verifikasi Dokumen NDA Peserta (Status Approved)
        NdaSubmission::firstOrCreate(
            ['user_id' => $peserta->id],
            [
                'team_id' => $team->id,
                'document_number' => 'NDA-2026-0001-A1B2',
                'template_version' => '1.0',
                'signed_file_path' => 'nda/sample_approved.pdf',
                'signed_file_sha256' => hash('sha256', 'sample_approved_nda'),
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'submitted_at' => now(),
            ]
        );

        // 6. Buat Contoh Laporan Siap Triage
        $scope = Scope::first();
        $sqliCwe = VulnerabilityType::where('cwe_id', 'CWE-89')->first() ?? VulnerabilityType::first();
        $xssCwe = VulnerabilityType::where('cwe_id', 'CWE-79')->first() ?? VulnerabilityType::first();

        if ($scope && $sqliCwe) {
            $report1 = Report::firstOrCreate(
                ['endpoint' => '/api/krs/filter_demo'],
                [
                    'team_id' => $team->id,
                    'reported_by' => $peserta->id,
                    'scope_id' => $scope->id,
                    'vulnerability_type_id' => $sqliCwe->id,
                    'title' => 'SQL Injection pada Filter Semester KRS',
                    'parameter' => 'semester_id',
                    'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H',
                    'cvss_score_claimed' => 9.8,
                    'severity_claimed' => 'critical',
                    'description' => 'Parameter semester_id pada endpoint /api/krs/filter_demo rentan terhadap SQL Injection tipe Boolean-based dan Time-based blind. Query dieksekusi langsung tanpa prepared statement.',
                    'steps_to_reproduce' => "1. Kirim HTTP POST ke /api/krs/filter_demo\n2. Masukkan payload: semester_id=1' AND (SELECT 1 FROM (SELECT(SLEEP(5)))a)-- -\n3. Response tertunda selama 5 detik membuktikan eksekusi query time-based.",
                    'impact' => 'Penyerang tanpa hak akses (unauthenticated) dapat mengekstrak seluruh data kredensial, nilai, dan informasi pribadi sivitas akademika.',
                    'remediation' => 'Gunakan Parameterized Query / Eloquent ORM prepared statements dan validasi tipe data integer secara ketat.',
                    'status' => 'submitted',
                    'submitted_at' => now()->subHours(3),
                ]
            );

            // Laporan 2: Needs Info
            if ($xssCwe) {
                $report2 = Report::firstOrCreate(
                    ['endpoint' => '/search/mahasiswa_demo'],
                    [
                        'team_id' => $team->id,
                        'reported_by' => $peserta->id,
                        'scope_id' => $scope->id,
                        'vulnerability_type_id' => $xssCwe->id,
                        'title' => 'Reflected XSS pada Kotak Pencarian Mahasiswa',
                        'parameter' => 'q',
                        'cvss_vector_claimed' => 'CVSS:3.1/AV:N/AC:L/PR:N/UI:R/S:C/C:L/I:L/A:N',
                        'cvss_score_claimed' => 6.1,
                        'severity_claimed' => 'medium',
                        'description' => 'Input pencarian parameter q dipantulkan langsung ke response HTML tanpa encoding karakter khusus.',
                        'steps_to_reproduce' => "1. Buka URL /search/mahasiswa_demo?q=%3Cscript%3Ealert(document.domain)%3C/script%3E\n2. Kotak alert Javascript muncul dengan nilai domain siakad.",
                        'impact' => 'Penyerang dapat menjalankan skrip sembarang pada konteks sesi pengguna yang mengklik tautan jahat.',
                        'remediation' => 'Lakukan HTML Entity Encoding (htmlspecialchars) pada seluruh output pencarian sebelum ditampilkan di Blade template.',
                        'status' => 'needs_info',
                        'submitted_at' => now()->subHours(6),
                    ]
                );

                ReportComment::firstOrCreate(
                    ['report_id' => $report2->id, 'user_id' => $admin->id],
                    [
                        'body' => 'Mohon sertakan bukti PoC bypass CSP (Content-Security-Policy) jika header CSP saat ini aktif di target.',
                        'is_internal' => false,
                    ]
                );

                ReportComment::firstOrCreate(
                    ['report_id' => $report2->id, 'user_id' => $admin->id, 'is_internal' => true],
                    [
                        'body' => 'Catatan internal triager: Cek apakah target memakai Cloudflare atau WAF internal yang memblokir tag script.',
                        'is_internal' => true,
                    ]
                );
            }
        }
    }
}
