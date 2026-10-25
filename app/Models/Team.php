<?php

namespace App\Models;

use App\Services\CompetitionSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'invite_code',
        'leader_id',
        'report_quota_override',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'report_quota_override' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Team $team) {
            if (empty($team->slug)) {
                $team->slug = Str::slug($team->name);
            }
            if (empty($team->invite_code)) {
                $team->invite_code = self::generateUniqueInviteCode();
            }
        });
    }

    public static function generateUniqueInviteCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('invite_code', $code)->exists());

        return $code;
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_members')
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function ndaSubmissions(): HasMany
    {
        return $this->hasMany(NdaSubmission::class);
    }

    public function activeReportsCount(): int
    {
        return $this->reports()
            ->whereIn('status', ['submitted', 'in_review'])
            ->count();
    }

    public function totalReportsCount(): int
    {
        return $this->reports()->count();
    }

    public function effectiveQuota(): int
    {
        if ($this->report_quota_override !== null) {
            return (int) $this->report_quota_override;
        }

        return (int) app(CompetitionSettings::class)->get('max_active_reports_per_team', 5);
    }

    public function remainingQuota(): int
    {
        return max(0, $this->effectiveQuota() - $this->activeReportsCount());
    }

    public function hasQuotaForNewReport(): bool
    {
        if ($this->status === 'disqualified') {
            return false;
        }

        // Cek kuota aktif
        if ($this->activeReportsCount() >= $this->effectiveQuota()) {
            return false;
        }

        // Cek total batas laporan jika dikonfigurasi
        $maxTotal = app(CompetitionSettings::class)->get('max_total_reports_per_team');
        if ($maxTotal !== null && $this->totalReportsCount() >= (int) $maxTotal) {
            return false;
        }

        return true;
    }

    public function isDisqualified(): bool
    {
        return $this->status === 'disqualified';
    }
}
