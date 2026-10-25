<?php

namespace App\Services;

use App\Models\CompetitionSetting;
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

    /**
     * Menyimpan satu setting dan meng-invalidasi cache bersama seketika.
     */
    public function set(string $key, mixed $value, ?int $updatedBy = null): void
    {
        $this->validateKey($key, $value);

        CompetitionSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => ['value' => $value],
                'updated_by' => $updatedBy,
            ]
        );

        $this->flushCache();
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
            CompetitionSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => ['value' => $value],
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

            case 'registration_rate_limit_per_hour':
                if (! is_int($value) || $value < 1) {
                    throw new InvalidArgumentException('Batas laju registrasi per jam harus berupa bilangan bulat >= 1.');
                }
                break;
        }
    }

    /**
     * Cek apakah pendaftaran saat ini sedang terbuka
     */
    public function isRegistrationOpen(): bool
    {
        $now = now();
        $openAt = $this->get('registration_open_at');
        $closeAt = $this->get('registration_close_at');

        if ($openAt && $now->lt($openAt)) {
            return false;
        }
        if ($closeAt && $now->gt($closeAt)) {
            return false;
        }

        return true;
    }

    /**
     * Cek apakah pengiriman laporan saat ini sedang terbuka
     */
    public function isSubmissionOpen(): bool
    {
        $now = now();
        $openAt = $this->get('submission_open_at');
        $closeAt = $this->get('submission_close_at');

        if ($openAt && $now->lt($openAt)) {
            return false;
        }
        if ($closeAt && $now->gt($closeAt)) {
            return false;
        }

        return true;
    }
}
