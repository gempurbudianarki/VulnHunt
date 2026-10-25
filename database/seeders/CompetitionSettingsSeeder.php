<?php

namespace Database\Seeders;

use App\Services\CompetitionSettings;
use Illuminate\Database\Seeder;

class CompetitionSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settingsService = app(CompetitionSettings::class);

        $defaultSettings = [
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
            'leaderboard_mode' => 'visible',
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
        ];

        $settingsService->setMany($defaultSettings);
    }
}
