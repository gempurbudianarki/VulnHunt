<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'revision_no',
        'snapshot',
        'created_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            // Snapshot wajib terenkripsi agar tidak membocorkan data payload
            'snapshot' => 'encrypted:array',
            'created_at' => 'datetime',
            'revision_no' => 'integer',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
