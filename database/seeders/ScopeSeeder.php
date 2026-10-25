<?php

namespace Database\Seeders;

use App\Models\Scope;
use Illuminate\Database\Seeder;

class ScopeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $scopes = [
            [
                'name' => 'Portal Mahasiswa UBBG (Placeholder)',
                'target' => 'https://portal-demo.ubbg.ac.id',
                'asset_type' => 'web',
                'scope_status' => 'in_scope',
                'criticality_weight' => 1.50,
                'description' => 'Sistem informasi akademik, portal mahasiswa, dan KRS.',
                'notes' => 'Pengujian hanya diperkenankan pada jam 08:00 - 22:00 WIB.',
                'is_active' => true,
            ],
            [
                'name' => 'API Gateway Kampus (Placeholder)',
                'target' => 'https://api-demo.ubbg.ac.id',
                'asset_type' => 'api',
                'scope_status' => 'in_scope',
                'criticality_weight' => 1.80,
                'description' => 'Layanan backend REST API endpoint mobile mahasiswa dan dosen.',
                'notes' => 'Wajib menyertakan header User-Agent bertanda tangan lomba.',
                'is_active' => true,
            ],
            [
                'name' => 'LMS Elearning UBBG (Placeholder)',
                'target' => 'https://lms-demo.ubbg.ac.id',
                'asset_type' => 'web',
                'scope_status' => 'in_scope',
                'criticality_weight' => 1.20,
                'description' => 'Platform pembelajaran daring mahasiswa.',
                'notes' => 'Tidak diperbolehkan mengubah materi kuliah atau tugas aktif.',
                'is_active' => true,
            ],
            [
                'name' => 'Payment Gateway & Rekening Kampus (Out of Scope)',
                'target' => 'https://payment-demo.ubbg.ac.id',
                'asset_type' => 'web',
                'scope_status' => 'out_of_scope',
                'criticality_weight' => 1.00,
                'description' => 'Sistem pembayaran SPP dan perbankan pihak ketiga.',
                'notes' => 'DILARANG KERAS diuji. Segala bentuk pengujian otomatis atau manual akan langsung didiskualifikasi.',
                'is_active' => true,
            ],
        ];

        foreach ($scopes as $scope) {
            Scope::firstOrCreate(
                ['target' => $scope['target']],
                $scope
            );
        }
    }
}
