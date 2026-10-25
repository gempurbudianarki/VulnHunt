<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::role('admin')->first();

        Announcement::firstOrCreate(
            ['title' => 'Selamat Datang di Platform UBBG VulnHunt 2026'],
            [
                'body' => "Selamat datang para peserta lomba bug bounty internal UBBG!\n\nPastikan seluruh anggota tim Anda telah melengkapi verifikasi email, menyetujui Rules of Engagement, dan mengunggah lembar perjanjian kerahasiaan (NDA) yang telah ditandatangani. Akses scope dan formulir laporan akan otomatis terbuka segera setelah NDA disetujui panitia.\n\nSelamat berburu secara etis dan junjung tinggi sportivitas!",
                'is_pinned' => true,
                'published_at' => now(),
                'created_by' => $admin?->id,
            ]
        );
    }
}
