<?php

namespace App\Http\Controllers;

use App\Models\NdaSubmission;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();
        $latestNda = $user->latestNdaSubmission;
        $history = $user->ndaSubmissions()->orderBy('created_at', 'desc')->get();

        return view('verification.index', compact(
            'user',
            'team',
            'latestNda',
            'history'
        ));
    }

    public function downloadTemplate(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();
        $documentNumber = NdaSubmission::generateDocumentNumber($user->id);
        $settingsService = app(\App\Services\CompetitionSettings::class);
        $templateVersion = (string) $settingsService->get('nda_template_version', '1.0');
        $customText = $settingsService->get('nda_template_text', null);

        $pdf = Pdf::loadView('pdf.nda-template', compact(
            'user',
            'team',
            'documentNumber',
            'templateVersion',
            'customText'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("Template-NDA-{$user->nim}-{$documentNumber}.pdf");
    }

    public function uploadSigned(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();
        $latestNda = $user->latestNdaSubmission;

        // Keputusan user: Peserta berstatus approved tidak bisa unggah ulang yang menjatuhkan statusnya
        if ($latestNda && $latestNda->isApproved()) {
            return back()->with('error', 'Dokumen NDA Anda sudah disetujui oleh panitia dan tidak dapat diunggah ulang.');
        }

        $request->validate([
            'signed_file' => ['required', 'file', 'max:5120'], // Max 5 MB
        ], [
            'signed_file.required' => 'Silakan pilih berkas hasil pindai NDA bertanda tangan Anda.',
            'signed_file.max' => 'Ukuran berkas NDA maksimal adalah 5 MB.',
        ]);

        $file = $request->file('signed_file');
        $tempPath = $file->getRealPath();

        // 1. Validasi MIME asli (finfo)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $tempPath);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
        ];

        if (! array_key_exists($realMime, $allowedMimes)) {
            throw ValidationException::withMessages([
                'signed_file' => ['Format berkas tidak valid. Berkas NDA hanya boleh berupa PDF, JPG, atau PNG asli.'],
            ]);
        }

        $extension = $allowedMimes[$realMime];
        $storedName = (string) Str::uuid().".{$extension}";
        $relativeDiskPath = "nda/{$storedName}";

        // Pastikan folder private/nda ada
        Storage::disk('private')->makeDirectory('nda');
        $fullDestinationPath = Storage::disk('private')->path($relativeDiskPath);

        // 2. Pemrosesan jika berupa Gambar: Batasi dimensi piksel, auto-resize & strip EXIF
        if (in_array($extension, ['jpg', 'png'], true)) {
            $imageInfo = @getimagesize($tempPath);
            if (! $imageInfo) {
                throw ValidationException::withMessages([
                    'signed_file' => ['Berkas gambar rusak atau tidak valid.'],
                ]);
            }

            [$width, $height] = $imageInfo;
            // Batasi dimensi piksel maksimal 6000x6000 piksel sebelum didecode
            if ($width > 6000 || $height > 6000) {
                throw ValidationException::withMessages([
                    'signed_file' => ["Dimensi gambar terlalu besar (maksimal 6000x6000 piksel, dimensi berkas Anda: {$width}x{$height} piksel)."],
                ]);
            }

            // Strip EXIF metadata dan resize otomatis bila melebihi 2500x2500 piksel
            try {
                $manager = new ImageManager(new Driver);
                $image = $manager->read($tempPath);
                $image->scaleDown(2500, 2500);
                $image->save($fullDestinationPath);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'signed_file' => ['Gagal memproses berkas gambar: memori server tidak mencukupi atau berkas gambar rusak.'],
                ]);
            }
        } else {
            // Berkas PDF disimpan langsung ke storage privat
            copy($tempPath, $fullDestinationPath);
        }

        // 3. Hitung SHA-256 dari berkas hasil olahan
        $sha256 = hash_file('sha256', $fullDestinationPath);

        // 4. Generate nomor dokumen baru untuk pengajuan ini
        $docNumber = NdaSubmission::generateDocumentNumber($user->id);

        NdaSubmission::create([
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'document_number' => $docNumber,
            'template_version' => '1.0',
            'signed_file_path' => $relativeDiskPath,
            'signed_file_sha256' => $sha256,
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return redirect()->route('verification.index')
            ->with('success', 'Dokumen NDA bertanda tangan berhasil diunggah! Status pengajuan kini menunggu review panitia.');
    }

    public function download(Request $request, int $id)
    {
        $user = $request->user();
        $submission = NdaSubmission::findOrFail($id);

        // Akses hanya untuk pemilik berkas atau panitia role admin (triager dilarang akses dokumen NDA)
        if ($submission->user_id !== $user->id && ! $user->hasRole('admin')) {
            abort(403, 'Anda tidak berhak mengakses dokumen ini.');
        }

        // Catat unduhan berkas NDA oleh admin ke audit log aktivitas
        if ($user->hasRole('admin') && $submission->user_id !== $user->id) {
            activity('nda')
                ->performedOn($submission)
                ->causedBy($user)
                ->withProperties([
                    'nda_id' => $submission->id,
                    'document_number' => $submission->document_number,
                    'target_user_id' => $submission->user_id,
                    'ip' => $request->ip(),
                ])
                ->log('admin_download_nda');
        }

        if (! Storage::disk('private')->exists($submission->signed_file_path)) {
            abort(404, 'Berkas fisik tidak ditemukan di sistem penyimpanan privat.');
        }

        $extension = pathinfo($submission->signed_file_path, PATHINFO_EXTENSION);
        $downloadFilename = "NDA-{$submission->document_number}.{$extension}";

        return Storage::disk('private')->download($submission->signed_file_path, $downloadFilename, [
            'Content-Disposition' => "attachment; filename=\"{$downloadFilename}\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
