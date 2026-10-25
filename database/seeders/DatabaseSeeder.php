<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CompetitionSettingsSeeder::class,
            InitialAdminSeeder::class,
            CweCatalogSeeder::class,
            ScopeSeeder::class,
            AnnouncementSeeder::class,
        ]);
    }
}
