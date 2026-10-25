<?php

namespace App\Models;

use App\Services\CompetitionSettings;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'nim',
        'email',
        'phone',
        'study_program',
        'cohort_year',
        'password',
        'rules_accepted_version',
        'rules_accepted_at',
        'rules_accepted_ip',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'rules_accepted_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'cohort_year' => 'integer',
        ];
    }

    /**
     * Filament Panel access gate.
     * Only admin and triager can access, and they must have 2FA enabled in production.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->hasAnyRole(['admin', 'triager'])) {
            return false;
        }

        // Admin & triager wajib 2FA sebelum akses panel
        if (app()->isProduction() && ! $this->hasEnabledTwoFactorAuthentication()) {
            return false;
        }

        return true;
    }

    public function teamMember(): HasOne
    {
        return $this->hasOne(TeamMember::class);
    }

    public function currentTeam(): ?Team
    {
        return $this->teamMember?->team;
    }

    public function team(): HasOneThrough
    {
        return $this->hasOneThrough(Team::class, TeamMember::class, 'user_id', 'id', 'id', 'team_id');
    }

    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    public function ndaSubmissions(): HasMany
    {
        return $this->hasMany(NdaSubmission::class);
    }

    public function latestNdaSubmission(): HasOne
    {
        return $this->hasOne(NdaSubmission::class)->latestOfMany();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_by');
    }

    /**
     * Helper to verify if participant has cleared all gate criteria:
     * 1. Email verified
     * 2. Latest rules accepted
     * 3. Latest NDA approved
     */
    public function isApprovedParticipant(?string $currentRulesVersion = null): bool
    {
        if (! $this->hasVerifiedEmail()) {
            return false;
        }

        $activeRulesVersion = $currentRulesVersion ?? (string) app(CompetitionSettings::class)->get('rules_version', '1');
        if ($this->rules_accepted_version !== $activeRulesVersion) {
            return false;
        }

        $latestNda = $this->latestNdaSubmission;

        return $latestNda && $latestNda->status === 'approved';
    }
}
