<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class VulnHuntPreflightCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vulnhunt:preflight {--format=table : Format output: table atau json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pemeriksaan keamanan dan kesiapan preflight sebelum deployment produksi UBBG VulnHunt';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== UBBG VULNHUNT PRODUCTION PREFLIGHT AUDIT ===');
        $this->line('Menjalankan 12 pemeriksaan integritas, arsitektur, dan keamanan produksi...' . PHP_EOL);

        $checks = [];

        // 1. APP_DEBUG harus false
        $debug = config('app.debug');
        $checks[] = [
            'no' => 1,
            'kriteria' => 'APP_DEBUG dinonaktifkan (false)',
            'hasil' => ! $debug ? 'PASS' : 'FAIL',
            'detail' => $debug ? 'APP_DEBUG saat ini bernilai true (kebocoran stack trace/info internal).' : 'Aman (false)',
        ];

        // 2. APP_ENV harus production
        $env = app()->environment();
        $checks[] = [
            'no' => 2,
            'kriteria' => 'APP_ENV bernilai "production"',
            'hasil' => $env === 'production' ? 'PASS' : 'FAIL',
            'detail' => $env === 'production' ? 'Production mode aktif.' : "Environment saat ini: {$env} (bukan production).",
        ];

        // 3. APP_URL harus https
        $url = config('app.url');
        $isHttps = str_starts_with((string) $url, 'https://');
        $checks[] = [
            'no' => 3,
            'kriteria' => 'APP_URL menggunakan skema HTTPS (https://)',
            'hasil' => $isHttps ? 'PASS' : 'FAIL',
            'detail' => $isHttps ? 'Valid HTTPS URL.' : "URL saat ini: {$url} (wajib diawali https://).",
        ];

        // 4. Tidak ada akun demo di database
        $demoUsersCount = User::where('email', 'like', '%.demo@%')
            ->orWhere('email', 'peserta@ubbg.ac.id')
            ->count();
        $checks[] = [
            'no' => 4,
            'kriteria' => 'Nol akun demo pada database (*.demo@*, peserta@ubbg.ac.id)',
            'hasil' => $demoUsersCount === 0 ? 'PASS' : 'FAIL',
            'detail' => $demoUsersCount === 0 ? 'Bersih (0 akun demo).' : "Ditemukan {$demoUsersCount} akun demo di database.",
        ];

        // 5. Akun staf tidak boleh masih must_change_password
        $staffRoles = \Spatie\Permission\Models\Role::whereIn('name', ['admin', 'triager'])->pluck('name')->toArray();
        $staffPendingPassword = ! empty($staffRoles)
            ? User::role($staffRoles)->where('must_change_password', true)->count()
            : 0;
        $checks[] = [
            'no' => 5,
            'kriteria' => 'Seluruh akun staf telah mengganti password default (must_change_password = false)',
            'hasil' => $staffPendingPassword === 0 ? 'PASS' : 'FAIL',
            'detail' => $staffPendingPassword === 0 ? 'Seluruh staf telah menetapkan password aman.' : "Ada {$staffPendingPassword} akun staf dengan must_change_password=true.",
        ];

        // 6. MAIL_MAILER tidak boleh log
        $mailer = config('mail.default');
        $checks[] = [
            'no' => 6,
            'kriteria' => 'MAIL_MAILER dikonfigurasi ke driver pengiriman nyata (bukan log)',
            'hasil' => $mailer !== 'log' ? 'PASS' : 'FAIL',
            'detail' => $mailer !== 'log' ? "Driver mail: {$mailer}" : 'MAIL_MAILER masih bernilai "log" (email tidak terkirim ke pengguna).',
        ];

        // 7. QUEUE_CONNECTION tidak boleh sync
        $queue = config('queue.default');
        $checks[] = [
            'no' => 7,
            'kriteria' => 'QUEUE_CONNECTION menggunakan background worker (bukan sync)',
            'hasil' => $queue !== 'sync' ? 'PASS' : 'FAIL',
            'detail' => $queue !== 'sync' ? "Queue driver: {$queue}" : 'QUEUE_CONNECTION masih bernilai "sync" (blocking HTTP worker).',
        ];

        // 8. CACHE_STORE tidak boleh array
        $cache = config('cache.default');
        $checks[] = [
            'no' => 8,
            'kriteria' => 'CACHE_STORE persisten (bukan array)',
            'hasil' => $cache !== 'array' ? 'PASS' : 'FAIL',
            'detail' => $cache !== 'array' ? "Cache store: {$cache}" : 'CACHE_STORE masih bernilai "array" (rate limiter/kuota reset tiap request).',
        ];

        // 9. APP_KEY tidak boleh kosong
        $appKey = config('app.key');
        $checks[] = [
            'no' => 9,
            'kriteria' => 'APP_KEY terisi dan valid',
            'hasil' => ! empty($appKey) ? 'PASS' : 'FAIL',
            'detail' => ! empty($appKey) ? 'APP_KEY terkonfigurasi.' : 'APP_KEY kosong! Enkripsi data laporan gagal.',
        ];

        // 10. SESSION_SECURE_COOKIE harus true
        $sessionSecure = config('session.secure');
        $checks[] = [
            'no' => 10,
            'kriteria' => 'SESSION_SECURE_COOKIE aktif (true, cookie https-only)',
            'hasil' => $sessionSecure === true ? 'PASS' : 'FAIL',
            'detail' => $sessionSecure === true ? 'Cookie session secure aktif.' : 'SESSION_SECURE_COOKIE bernilai false/null (rentan sniffing MITM).',
        ];

        // 11. storage/app/private tidak boleh bisa diakses via public/storage
        $publicStorage = public_path('storage');
        $privateStorage = storage_path('app/private');
        $isPrivateExposed = false;
        $privateCheckMsg = 'storage/app/private terisolasi penuh dari web root publik.';

        if (File::exists($publicStorage)) {
            $realPublic = realpath($publicStorage);
            $realPrivate = realpath($privateStorage);
            if ($realPrivate && $realPublic && str_starts_with($realPrivate, $realPublic)) {
                $isPrivateExposed = true;
                $privateCheckMsg = 'BAHAYA: storage/app/private berada di dalam direktori public/storage!';
            } elseif (File::exists($publicStorage . '/private')) {
                $isPrivateExposed = true;
                $privateCheckMsg = 'BAHAYA: Ditemukan folder/symlink private di public/storage!';
            }
        }

        $checks[] = [
            'no' => 11,
            'kriteria' => 'storage/app/private terisolasi (tidak dapat diakses via public/storage)',
            'hasil' => ! $isPrivateExposed ? 'PASS' : 'FAIL',
            'detail' => $privateCheckMsg,
        ];

        // 12. CSP tidak boleh masih report-only
        $cspReportOnly = config('security.csp_report_only', env('CSP_REPORT_ONLY', true));
        $checks[] = [
            'no' => 12,
            'kriteria' => 'Content-Security-Policy (CSP) aktif dalam mode ENFORCE (bukan report-only)',
            'hasil' => ($cspReportOnly === false || $cspReportOnly === 'false') ? 'PASS' : 'FAIL',
            'detail' => ($cspReportOnly === false || $cspReportOnly === 'false') 
                ? 'CSP Enforce aktif.' 
                : 'CSP masih dalam mode Report-Only (CSP_REPORT_ONLY=true). Serangan XSS tidak diblokir.',
        ];

        // Render Table
        $this->table(
            ['No', 'Pemeriksaan / Kriteria Keamanan', 'Status', 'Catatan / Tindak Lanjut'],
            array_map(fn ($c) => [
                $c['no'],
                $c['kriteria'],
                $c['hasil'] === 'PASS' ? '<info>PASS</info>' : '<error>FAIL</error>',
                $c['detail'],
            ], $checks)
        );

        $failCount = count(array_filter($checks, fn ($c) => $c['hasil'] === 'FAIL'));

        if ($failCount > 0) {
            $this->newLine();
            $this->error(">>> PREFLIGHT PRODUKSI GAGAL: Terdapat {$failCount} dari " . count($checks) . " pemeriksaan yang belum memenuhi standar produksi. <<<");
            $this->line('Perbaiki seluruh variabel environment dan konfigurasi di atas sebelum mengaktifkan sistem di lingkungan produksi.');

            return 1;
        }

        $this->newLine();
        $this->info('>>> PREFLIGHT PRODUKSI LULUS: Seluruh 12 kriteria keamanan dan infrastruktur telah terverifikasi aman. Sistem siap melayani lalu lintas produksi. <<<');

        return 0;
    }
}
