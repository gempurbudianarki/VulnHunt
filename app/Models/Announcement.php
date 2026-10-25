<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'body',
        'is_pinned',
        'published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Announcement $announcement) {
            if ($announcement->body !== null) {
                $announcement->body = \App\Services\MarkdownSanitizer::clean($announcement->body);
            }

            if (! $announcement->created_by && auth()->check()) {
                $announcement->created_by = auth()->id();
            }
        });

        static::created(function (Announcement $announcement) {
            if ($announcement->is_pinned) {
                try {
                    $participants = User::role('participant')->where('is_active', true)->get();
                    foreach ($participants as $p) {
                        $p->notify(new \App\Notifications\ImportantAnnouncementNotification($announcement));
                    }
                } catch (\Throwable $e) {
                    // Jangan gagalkan pembuatan pengumuman jika antrean gagal
                }
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function getRenderedBodyAttribute(): string
    {
        return \App\Services\MarkdownSanitizer::render($this->body);
    }
}
