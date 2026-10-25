<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NdaSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'document_number',
        'template_version',
        'generated_pdf_path',
        'signed_file_path',
        'signed_file_sha256',
        'status',
        'reject_reason',
        'reviewed_by',
        'reviewed_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * Generate document number format: NDA-{tahun}-{user_id 4 digit}-{4 hex acak}
     * Sesuai spesifikasi Bagian 8.8
     */
    public static function generateDocumentNumber(int $userId): string
    {
        $year = date('Y');
        $paddedUserId = str_pad((string) $userId, 4, '0', STR_PAD_LEFT);

        do {
            $randomHex = strtoupper(bin2hex(random_bytes(2))); // 4 karakter heksadesimal
            $docNumber = "NDA-{$year}-{$paddedUserId}-{$randomHex}";
        } while (self::where('document_number', $docNumber)->exists());

        return $docNumber;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
