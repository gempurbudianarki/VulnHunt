<?php

namespace App\Services;

use App\Models\CompetitionSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class CompetitionSettings
{
    public const CACHE_KEY = 'competition_settings_all';

    public const CACHE_TTL = 86400; // 24 jam, di-flush seketika saat diubah

    /**
     * Nilai default resmi sesuai spesifikasi Bagian 8.1
     */
    protected array $defaults = [
        'max_active_reports_per_team' => 5,
        'max_total_reports_per_team' => null,
        'min_team_size' => 2,
        'max_team_size' => 4,
        'registration_open_at' => null,
        'registration_close_at' => null,
        'submission_open_at' => null,
        'submission_close_at' => null,
        'points_per_severity' => [
            'critical' => 100,
            'high' => 60,
            'medium' => 30,
            'low' => 10,
            'info' => 2,
        ],
        'duplicate_points' => 0,
        'diminishing_enabled' => false,
        'diminishing_steps' => [1.0, 0.5, 0.25],
        'leaderboard_mode' => 'visible', // visible, hidden, hidden_until_end
        'triage_weights' => [
            'critical' => 40,
            'high' => 25,
            'medium' => 10,
            'low' => 5,
            'info' => 1,
        ],
        'triage_age_factor' => 0.5,
        'rules_version' => '1',
        'allowed_email_domains' => ['ubbg.ac.id'],
        'registration_rate_limit_per_hour' => 60,
        'report_rate_limit_per_hour' => 10,
        'upload_rate_limit_per_minute' => 20,
        'comment_rate_limit_per_minute' => 30,
    ];

    /**
     * Mengambil seluruh setting menggunakan shared cache (bukan in-memory statis).
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $settings = $this->defaults;

            try {
                $dbSettings = CompetitionSetting::all();
                foreach ($dbSettings as $item) {
                    $val = $item->value;
                    // Format wrapper {"value": ...} atau raw JSON
                    if (is_array($val) && array_key_exists('value', $val)) {
                        $settings[$item->key] = $val['value'];
                    } else {
                        $settings[$item->key] = $val;
                    }
                }
            } catch (\Throwable $e) {
                // Fallback ke default jika tabel belum ada atau koneksi gagal
            }

            return $settings;
        });
    }

    /**
     * Mengambil satu setting dengan fallback default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        if (array_key_exists($key, $this->defaults)) {
            return $this->defaults[$key];
        }

        return $default;
    }

    public const DATE_KEYS = [
        'registration_open_at',
        'registration_close_at',
        'submission_open_at',
        'submission_close_at',
    ];

    /**
     * Mengambil daftar seluruh nilai default resmi
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Mengambil nilai default resmi untuk satu kunci
     */
    public function getDefault(string $key): mixed
    {
        return $this->defaults[$key] ?? null;
    }

    /**
     * Reset nilai pengaturan ke default resmi
     */
    public function reset(string $key, ?int $updatedBy = null): void
    {
        if (! array_key_exists($key, $this->defaults)) {
            throw new InvalidArgumentException("Kunci pengaturan {$key} tidak memiliki nilai default.");
        }

        $this->set($key, $this->defaults[$key], $updatedBy);
    }

    /**
     * Memastikan seluruh kunci default tersimpan di tabel database
     */
    public function ensureAllKeysSeeded(): void
    {
        foreach ($this->defaults as $key => $defaultVal) {
            if (! CompetitionSetting::where('key', $key)->exists()) {
                CompetitionSetting::create([
                    'key' => $key,
                    'value' => ['value' => $defaultVal],
                ]);
            }
        }
    }

    /**
     * Menyimpan satu setting dan meng-invalidasi cache bersama seketika.
     */
    public function set(string $key, mixed $value, ?int $updatedBy = null): void
    {
        $this->validateKey($key, $value);
        $savedValue = $value instanceof Carbon ? $value->format('Y-m-d H:i:s') : ($value === '' ? null : $value);
        $oldValue = $this->get($key);

        $causerId = $updatedBy ?? (auth()->check() ? auth()->id() : null);

        CompetitionSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => ['value' => $savedValue],
                'updated_by' => $causerId,
            ]
        );

        $this->flushCache();

        if (function_exists('activity')) {
            try {
                $causer = $causerId ? \App\Models\User::find($causerId) : null;
                $activity = activity('competition_settings')
                    ->withProperties([
                        'key' => $key,
                        'old' => $oldValue,
                        'new' => $savedValue,
                    ]);

                if ($causer) {
                    $activity->causedBy($causer);
                }

                $activity->log("Pengaturan {$key} diperbarui");
            } catch (\Throwable $e) {
                // Jangan gagalkan penyimpanan jika log gagal
            }
        }
    }

    /**
     * Menyimpan banyak setting sekaligus dalam transaksi
     */
    public function setMany(array $settings, ?int $updatedBy = null): void
    {
        foreach ($settings as $key => $value) {
            $this->validateKey($key, $value);
        }

        foreach ($settings as $key => $value) {
            $savedValue = $value instanceof Carbon ? $value->format('Y-m-d H:i:s') : ($value === '' ? null : $value);
            CompetitionSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => ['value' => $savedValue],
                    'updated_by' => $updatedBy,
                ]
            );
        }

        $this->flushCache();
    }

    /**
     * Hapus shared cache agar perubahan langsung terbaca di seluruh request
     */
    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);

        try {
            app(LeaderboardService::class)->invalidateCache();
        } catch (\Throwable $e) {
            //
        }
    }

    /**
     * Validasi aturan angka & tipe data sesuai spesifikasi Bagian 8.1
     */
    public function validateKey(string $key, mixed $value): void
    {
        switch ($key) {
            case 'max_active_reports_per_team':
                if (! is_int($value) || $value < 1 || $value > 50) {
                    throw new InvalidArgumentException('Batas laporan aktif harus bilangan bulat 1 sampai 50.');
                }
                break;

            case 'max_total_reports_per_team':
                if ($value !== null && (! is_int($value) || $value < 1)) {
                    throw new InvalidArgumentException('Batas total laporan harus berupa null atau bilangan bulat >= 1.');
                }
                break;

            case 'min_team_size':
            case 'max_team_size':
                if (! is_int($value) || $value < 1 || $value > 10) {
                    throw new InvalidArgumentException('Ukuran tim harus bilangan bulat antara 1 sampai 10.');
                }
                break;

            case 'points_per_severity':
            case 'triage_weights':
                if (! is_array($value)) {
                    throw new InvalidArgumentException('Bobot poin harus berupa array terasosiasi.');
                }
                foreach ($value as $k => $v) {
                    if (! is_numeric($v) || $v < 0) {
                        throw new InvalidArgumentException("Bobot poin untuk {$k} tidak boleh negatif.");
                    }
                }
                break;

            case 'duplicate_points':
                if (! is_numeric($value) || $value < 0) {
                    throw new InvalidArgumentException('Poin duplikat tidak boleh negatif.');
                }
                break;

            case 'leaderboard_mode':
                if (! in_array($value, ['visible', 'hidden', 'hidden_until_end'], true)) {
                    throw new InvalidArgumentException('Mode papan peringkat tidak valid.');
                }
                break;

            case 'allowed_email_domains':
                if (! is_array($value)) {
                    throw new InvalidArgumentException('Daftar domain email harus berupa array.');
                }
                break;

            case 'rules_version':
                if (empty($value) || (! is_string($value) && ! is_numeric($value))) {
                    throw new InvalidArgumentException('Versi aturan (rules_version) harus berupa teks atau angka non-kosong.');
                }
                break;

            case 'diminishing_enabled':
                if (! is_bool($value)) {
                    throw new InvalidArgumentException('Status diminishing_enabled harus berupa boolean (true/false).');
                }
                break;

            case 'diminishing_steps':
                if (! is_array($value) || empty($value)) {
                    throw new InvalidArgumentException('Tahapan diminishing_steps harus berupa array angka.');
                }
                foreach ($value as $step) {
                    if (! is_numeric($step) || $step <= 0) {
                        throw new InvalidArgumentException('Setiap nilai diminishing_steps harus berupa angka positif.');
                    }
                }
                break;

            case 'registration_open_at':
            case 'registration_close_at':
            case 'submission_open_at':
            case 'submission_close_at':
                if ($value !== null && $value !== '') {
                    try {
                        Carbon::parse($value);
                    } catch (\Throwable $e) {
                        throw new InvalidArgumentException("Format tanggal untuk {$key} tidak valid.");
                    }
                }
                break;

            case 'registration_rate_limit_per_hour':
            case 'report_rate_limit_per_hour':
            case 'upload_rate_limit_per_minute':
            case 'comment_rate_limit_per_minute':
                if (! is_int($value) || $value < 1) {
                    throw new InvalidArgumentException("Batas laju {$key} harus berupa bilangan bulat >= 1.");
                }
                break;
        }
    }

    /**
     * Cek apakah pendaftaran saat ini sedang terbuka (perbandingan langsung dalam zona waktu WIB)
     */
    public function isRegistrationOpen(): bool
    {
        $now = now();
        $openAt = $this->get('registration_open_at');
        $closeAt = $this->get('registration_close_at');

        if ($openAt && $now->lt(Carbon::parse($openAt))) {
            return false;
        }
        if ($closeAt && $now->gt(Carbon::parse($closeAt))) {
            return false;
        }

        return true;
    }

    /**
     * Cek apakah pengiriman laporan saat ini sedang terbuka (perbandingan langsung dalam zona waktu WIB)
     */
    public function isSubmissionOpen(): bool
    {
        $now = now();
        $openAt = $this->get('submission_open_at');
        $closeAt = $this->get('submission_close_at');

        if ($openAt && $now->lt(Carbon::parse($openAt))) {
            return false;
        }
        if ($closeAt && $now->gt(Carbon::parse($closeAt))) {
            return false;
        }

        return true;
    }
}
