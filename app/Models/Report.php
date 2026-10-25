<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Report extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'team_id',
        'reported_by',
        'scope_id',
        'vulnerability_type_id',
        'title',
        'endpoint',
        'parameter',
        'cvss_vector_claimed',
        'cvss_score_claimed',
        'severity_claimed',
        'cvss_vector_final',
        'cvss_score_final',
        'severity_final',
        'description',
        'steps_to_reproduce',
        'impact',
        'remediation',
        'status',
        'quality_factor',
        'points_awarded',
        'points_override_note',
        'duplicate_of',
        'revision_no',
        'triaged_by',
        'triaged_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            // Cast encrypted mutlak untuk muatan sensitif eksploitasi
            'description' => 'encrypted',
            'steps_to_reproduce' => 'encrypted',
            'impact' => 'encrypted',
            'remediation' => 'encrypted',
            'cvss_score_claimed' => 'decimal:1',
            'cvss_score_final' => 'decimal:1',
            'quality_factor' => 'decimal:2',
            'points_awarded' => 'integer',
            'revision_no' => 'integer',
            'submitted_at' => 'datetime',
            'triaged_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Report $report) {
            // submitted_at ditetapkan mutlak oleh server saat laporan dibuat
            if (empty($report->submitted_at)) {
                $report->submitted_at = now();
            }
        });

        static::created(function (Report $report) {
            if (empty($report->code)) {
                $year = date('Y', strtotime($report->submitted_at ?? 'now'));
                $paddedId = str_pad((string) $report->id, 4, '0', STR_PAD_LEFT);
                $report->updateQuietly(['code' => "BB-{$year}-{$paddedId}"]);
            }
        });
    }

    /**
     * Spatie Activity Log Whitelist (logOnly).
     * Sesuai keputusan DECISIONS.md Poin 3: HANYA mencatat perubahan atribut metadata.
     * Tidak pernah mencatat isi kolom payload/eksperimen kerentanan.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'status',
                'severity_final',
                'points_awarded',
                'quality_factor',
                'duplicate_of',
                'cvss_score_final',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('report');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function scope(): BelongsTo
    {
        return $this->belongsTo(Scope::class);
    }

    public function vulnerabilityType(): BelongsTo
    {
        return $this->belongsTo(VulnerabilityType::class);
    }

    public function duplicateOfReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'duplicate_of');
    }

    public function duplicates(): HasMany
    {
        return $this->hasMany(Report::class, 'duplicate_of');
    }

    public function triagedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ReportVersion::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReportAttachment::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ReportComment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ReportStatusHistory::class);
    }

    /**
     * Normalisasi endpoint untuk pencocokan kartu laporan terkait (Bagian 8.6)
     */
    public function getNormalizedEndpointAttribute(): string
    {
        return self::normalizeEndpoint($this->endpoint);
    }

    public static function normalizeEndpoint(string $endpoint): string
    {
        // 1. Buang fragment (#) dan query string (?)
        $cleaned = strtok($endpoint, '?');
        $cleaned = strtok($cleaned, '#');

        // 2. Huruf kecil & trim
        $cleaned = strtolower(trim($cleaned));

        // 3. Ganti segmen angka atau UUID menjadi {id}
        // Pola UUID
        $cleaned = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', '{id}', $cleaned);
        // Pola integer ID di antara slash atau di akhir
        $cleaned = preg_replace('/\/\d+(\/|$)/', '/{id}$1', $cleaned);

        return $cleaned;
    }

    /**
     * Mengambil laporan lain yang mungkin terkait untuk bantuan deteksi duplikat manual (Bagian 8.6)
     */
    public function getRelatedReports(int $limit = 10)
    {
        return self::where('scope_id', $this->scope_id)
            ->where(function ($q) {
                $q->where('vulnerability_type_id', $this->vulnerability_type_id)
                    ->orWhere('endpoint', $this->endpoint);
            })
            ->where('id', '!=', $this->id)
            ->orderBy('submitted_at', 'asc')
            ->limit($limit)
            ->get();
    }
}
