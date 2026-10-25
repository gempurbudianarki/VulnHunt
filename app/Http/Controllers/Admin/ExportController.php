<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(
        protected CsvExportService $exportService
    ) {}

    /**
     * Ekspor seluruh data laporan ke CSV (Hanya Admin).
     */
    public function exportReports(Request $request): StreamedResponse
    {
        $this->ensureAdminAccess($request);

        return $this->exportService->exportReports($request->user());
    }

    /**
     * Ekspor data peringkat akhir ke CSV (Hanya Admin).
     */
    public function exportLeaderboard(Request $request): StreamedResponse
    {
        $this->ensureAdminAccess($request);

        return $this->exportService->exportLeaderboard($request->user());
    }

    /**
     * Verifikasi hak akses admin ketat. Triager dan Peserta mendapat 403 Forbidden.
     */
    protected function ensureAdminAccess(Request $request): void
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole('admin')) {
            abort(403, 'Akses ditolak. Fitur ekspor CSV hanya dapat diakses oleh Administrator.');
        }
    }
}
