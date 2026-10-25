<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'user_id',
        'body',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (ReportComment $comment) {
            if (! $comment->is_internal) {
                $report = $comment->report;
                if ($report) {
                    if ($report->reporter && (int) $comment->user_id !== (int) $report->reported_by) {
                        $report->reporter->notify(new \App\Notifications\NewCommentNotification($report, $comment));
                    }
                    if ($report->triager && (int) $comment->user_id === (int) $report->reported_by) {
                        $report->triager->notify(new \App\Notifications\NewCommentNotification($report, $comment));
                    }
                }
            }
        });
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
