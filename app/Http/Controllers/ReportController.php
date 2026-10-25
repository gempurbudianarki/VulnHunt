<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportComment;
use App\Models\ReportVersion;
use App\Models\Scope;
use App\Models\VulnerabilityType;
use App\Services\CompetitionSettings;
use App\Services\CvssCalculator;
use App\Services\ReportStateMachine;
use App\Services\ReportSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();

        $reports = collect();
        $remainingQuota = 0;
        $activeReportsCount = 0;
        $effectiveQuota = 0;

        if ($team) {
            $reports = Report::where('team_id', $team->id)
                ->with(['scope', 'vulnerabilityType'])
                ->latest('id')
                ->get();

            $activeReportsCount = $team->activeReportsCount();
            $effectiveQuota = $team->effectiveQuota();
            $remainingQuota = $team->remainingQuota();
        }

        return view('reports.index', compact(
            'team',
            'reports',
            'remainingQuota',
            'activeReportsCount',
            'effectiveQuota'
        ));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam();
        $settings = app(CompetitionSettings::class);

        if (! $team) {
            return redirect()->route('teams.index')
                ->with('error', 'Anda harus memiliki tim terlebih dahulu sebelum mengirim laporan.');
        }

        if (! $settings->isSubmissionOpen()) {
            return redirect()->route('reports.index')
                ->with('error', 'Jendela pengiriman laporan kerentanan saat ini sedang ditutup.');
        }

        if (! $team->hasQuotaForNewReport()) {
            return redirect()->route('reports.index')
                ->with('error', 'Kuota laporan aktif tim Anda telah penuh. Tunggu hingga laporan Anda diproses panitia.');
        }

        $scopes = Scope::where('scope_status', 'in_scope')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $vulnerabilityTypes = VulnerabilityType::where('is_active', true)
            ->orderBy('cwe_id')
            ->get();

        return view('reports.create', compact('team', 'scopes', 'vulnerabilityTypes'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $userTeam = $user->currentTeam();

        if (! $userTeam) {
            return redirect()->route('teams.index')
                ->with('error', 'Anda harus memiliki tim terlebih dahulu.');
        }

        // 1. Validasi Server Ketat (scope aktif in_scope, vulnerability_type aktif, steps min 100)
        $validated = $request->validate([
            'scope_id' => [
                'required',
                'integer',
                Rule::exists('scopes', 'id')
                    ->where('scope_status', 'in_scope')
                    ->where('is_active', true),
            ],
            'vulnerability_type_id' => [
                'required',
                'integer',
                Rule::exists('vulnerability_types', 'id')
                    ->where('is_active', true),
            ],
            'title' => ['required', 'string', 'min:5', 'max:255'],
            'endpoint' => ['required', 'string', 'min:1', 'max:255'],
            'parameter' => ['nullable', 'string', 'max:255'],
            'cvss_vector_claimed' => ['required', 'string'],
            'description' => ['required', 'string'],
            'steps_to_reproduce' => ['required', 'string', 'min:100'],
            'impact' => ['required', 'string'],
            'remediation' => ['nullable', 'string'],
            'agree_no_privacy_violation' => ['required', 'accepted'],
            'agree_no_destruction' => ['required', 'accepted'],
            'agree_in_scope_only' => ['required', 'accepted'],
            'attachments' => ['nullable', 'array', 'max:8'],
            'attachments.*' => ['file', 'max:25600'], // max 25MB per file
        ], [
            'scope_id.exists' => 'Target scope yang dipilih tidak valid atau sedang tidak aktif.',
            'vulnerability_type_id.exists' => 'Jenis kerentanan CWE yang dipilih tidak valid atau tidak aktif.',
            'steps_to_reproduce.min' => 'Langkah reproduksi wajib dijelaskan secara rinci (minimal 100 karakter).',
            'attachments.max' => 'Maksimal 8 berkas lampiran bukti.',
            'attachments.*.max' => 'Ukuran berkas lampiran maksimal 25 MB per berkas.',
            'agree_no_privacy_violation.accepted' => 'Anda wajib menyetujui pernyataan tidak mengakses/mengunduh data pribadi.',
            'agree_no_destruction.accepted' => 'Anda wajib menyetujui pernyataan tidak merusak layanan.',
            'agree_in_scope_only.accepted' => 'Anda wajib menyetujui pernyataan hanya menguji target dalam scope.',
        ]);

        // 2. Kalkulasi Ulang CVSS di Server (Abaikan skor klaim dari browser)
        try {
            $cvss = CvssCalculator::calculate($validated['cvss_vector_claimed']);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'cvss_vector_claimed' => ['Vector CVSS tidak valid: '.$e->getMessage()],
            ]);
        }

        // 3. Simpan Laporan via ReportSubmissionService (Pessimistic Locking & Anti-Race Condition)
        $report = app(ReportSubmissionService::class)->submit($user, $userTeam, $validated, $cvss);

        // 4. Proses Berkas Lampiran (jika ada)
        if ($request->hasFile('attachments')) {
            $this->processAttachments($request->file('attachments'), $report);
        }

        return redirect()->route('reports.show', $report->code)
            ->with('success', "Laporan kerentanan berhasil dikirim dengan kode {$report->code}!");
    }

    public function show(Request $request, string $code)
    {
        $user = $request->user();
        $team = $user->currentTeam();

        $query = Report::where('code', $code)
            ->with([
                'scope',
                'vulnerabilityType',
                'reporter',
                'team.members.user',
                'attachments',
                'statusHistories.changedBy',
                'comments' => function ($q) use ($user) {
                    if (! $user->hasAnyRole(['admin', 'triager'])) {
                        $q->where('is_internal', false);
                    }
                    $q->with('user')->oldest();
                },
                'versions',
            ]);

        // Proteksi IDOR: Hanya pemilik tim atau staf (admin/triager) yang dapat melihat laporan
        if (! $user->hasAnyRole(['admin', 'triager'])) {
            if (! $team) {
                abort(403, 'Anda tidak memiliki akses ke laporan ini.');
            }
            $query->where('team_id', $team->id);
        }

        $report = $query->firstOrFail();

        return view('reports.show', compact('report', 'team', 'user'));
    }

    public function downloadAttachment(Request $request, string $code, int $attachmentId)
    {
        $user = $request->user();
        $team = $user->currentTeam();

        $report = Report::where('code', $code)->firstOrFail();

        // Cek IDOR Laporan: Hanya anggota tim pemilik atau admin/triager
        if (! $user->hasAnyRole(['admin', 'triager'])) {
            if (! $team || $report->team_id !== $team->id) {
                abort(403, 'Anda tidak berhak mengakses lampiran laporan ini.');
            }
        }

        $attachment = ReportAttachment::where('report_id', $report->id)
            ->where('id', $attachmentId)
            ->firstOrFail();

        if (! Storage::disk('private')->exists($attachment->disk_path)) {
            abort(404, 'Berkas fisik lampiran tidak ditemukan.');
        }

        $rawName = basename($attachment->original_name);
        $cleanFilename = preg_replace('/[^\w\.\-\s]/u', '_', $rawName);

        // Setiap unduhan lampiran oleh admin/triager wajib dicatat di log aktivitas
        if ($user->hasAnyRole(['admin', 'triager'])) {
            activity()
                ->causedBy($user)
                ->performedOn($attachment)
                ->withProperties([
                    'report_code' => $report->code,
                    'attachment_id' => $attachment->id,
                    'filename' => $cleanFilename,
                ])
                ->log('admin_download_attachment');
        }

        return Storage::disk('private')->download($attachment->disk_path, $cleanFilename, [
            'Content-Disposition' => "attachment; filename=\"{$cleanFilename}\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Balasan / Revisi Laporan berstatus needs_info oleh Tim.
     * Invarian Opsi B: Transisi needs_info -> in_review DIKECUALIKAN dari cek kuota laporan aktif!
     */
    public function replyNeedsInfo(Request $request, string $code)
    {
        $user = $request->user();
        $team = $user->currentTeam();

        if (! $team) {
            abort(403, 'Anda tidak memiliki tim.');
        }

        $report = Report::where('code', $code)
            ->where('team_id', $team->id)
            ->firstOrFail();

        // Hanya laporan berstatus needs_info yang bisa dibalas/direvisi tim
        if ($report->status !== 'needs_info') {
            return back()->with('error', 'Laporan ini tidak sedang membutuhkan informasi tambahan (status bukan needs_info).');
        }

        $validated = $request->validate([
            'reply_message' => ['required', 'string', 'min:5'],
            'steps_to_reproduce' => ['nullable', 'string', 'min:100'],
            'attachments' => ['nullable', 'array', 'max:4'],
            'attachments.*' => ['file', 'max:25600'],
        ], [
            'reply_message.required' => 'Pesan tanggapan wajib diisi.',
            'steps_to_reproduce.min' => 'Revisi langkah reproduksi minimal 100 karakter jika disertakan.',
        ]);

        try {
            DB::transaction(function () use ($report, $user, $validated, $request) {
                // 1. Simpan komentar publik balasan tim
                ReportComment::create([
                    'report_id' => $report->id,
                    'user_id' => $user->id,
                    'is_internal' => false,
                    'body' => $validated['reply_message'],
                ]);

                // 2. Jika ada revisi steps_to_reproduce, perbarui laporan dan catat versi baru
                $newRevisionNo = $report->revision_no + 1;
                if (! empty($validated['steps_to_reproduce'])) {
                    $report->update([
                        'steps_to_reproduce' => $validated['steps_to_reproduce'],
                        'revision_no' => $newRevisionNo,
                    ]);

                    ReportVersion::create([
                        'report_id' => $report->id,
                        'revision_no' => $newRevisionNo,
                        'snapshot' => [
                            'title' => $report->title,
                            'description' => $report->description,
                            'steps_to_reproduce' => $validated['steps_to_reproduce'],
                            'impact' => $report->impact,
                            'remediation' => $report->remediation,
                            'cvss_vector' => $report->cvss_vector_claimed,
                            'cvss_score' => $report->cvss_score_claimed,
                            'severity' => $report->severity_claimed,
                            'change_summary' => 'Revisi langkah reproduksi atas permintaan admin.',
                        ],
                        'created_by' => $user->id,
                    ]);
                }

                // 3. Proses lampiran tambahan (jika ada)
                if ($request->hasFile('attachments')) {
                    $this->processAttachments($request->file('attachments'), $report);
                }

                // 4. Invarian Opsi B: Ubah status dari needs_info -> in_review melalui ReportStateMachine (EXEMPT dari cek kuota aktif)
                app(ReportStateMachine::class)->transition(
                    $report,
                    'in_review',
                    $user,
                    [
                        'is_team_reply' => true,
                        'reason' => 'Tim menanggapi permintaan informasi panitia.',
                    ]
                );
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('reports.show', $report->code)
            ->with('success', 'Tanggapan berhasil dikirim! Status laporan kini kembali menjadi Dalam Review (in_review).');
    }

    /**
     * Memproses berkas lampiran dengan validasi finfo MIME, pelarangan SVG,
     * batasan dimensi piksel 6000x6000px, EXIF stripping, dan hashing SHA-256.
     */
    protected function processAttachments(array $files, Report $report): void
    {
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'text/markdown' => 'md',
        ];

        Storage::disk('private')->makeDirectory('reports');

        foreach ($files as $file) {
            $realPath = $file->getRealPath();
            $originalName = $file->getClientOriginalName();
            $originalExt = strtolower($file->getClientOriginalExtension());

            // Larangan Mutlak Format SVG
            if ($originalExt === 'svg' || str_contains(strtolower($file->getClientMimeType()), 'svg')) {
                throw ValidationException::withMessages([
                    'attachments' => ['Berkas format SVG dilarang keras demi alasan keamanan Stored XSS.'],
                ]);
            }

            // Validasi MIME Asli via finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $realPath);
            finfo_close($finfo);

            // Periksa MIME terhadap whitelist
            if (! array_key_exists($realMime, $allowedMimes)) {
                // Beri toleransi untuk text/plain dengan ekstensi .md
                if ($realMime === 'text/plain' && $originalExt === 'md') {
                    $realMime = 'text/markdown';
                } else {
                    throw ValidationException::withMessages([
                        'attachments' => ["Format berkas '{$originalName}' ({$realMime}) tidak diizinkan. Berkas hanya boleh berupa PNG, JPG, WEBP, MP4, WEBM, PDF, TXT, atau MD."],
                    ]);
                }
            }

            $safeExt = $allowedMimes[$realMime] ?? $originalExt;
            $diskFileName = (string) Str::uuid().".{$safeExt}";
            $relativeDiskPath = "reports/{$diskFileName}";
            $fullDestinationPath = Storage::disk('private')->path($relativeDiskPath);

            // Pemrosesan khusus gambar: Batasi dimensi piksel, resize otomatis & strip EXIF
            if (in_array($safeExt, ['jpg', 'png', 'webp'], true)) {
                $imageInfo = @getimagesize($realPath);
                if (! $imageInfo) {
                    throw ValidationException::withMessages([
                        'attachments' => ["Berkas gambar '{$originalName}' rusak atau tidak dapat diverifikasi."],
                    ]);
                }

                [$width, $height] = $imageInfo;
                if ($width > 4096 || $height > 4096) {
                    throw ValidationException::withMessages([
                        'attachments' => ["Dimensi gambar '{$originalName}' ({$width}x{$height} piksel) melebihi batas maksimal 4096x4096 piksel."],
                    ]);
                }

                try {
                    $manager = new ImageManager(new Driver);
                    $image = $manager->read($realPath);
                    $image->scaleDown(2500, 2500);
                    $image->save($fullDestinationPath);
                } catch (\Throwable $e) {
                    throw ValidationException::withMessages([
                        'attachments' => ["Gagal memproses gambar '{$originalName}'. Memori server tidak mencukupi atau citra rusak."],
                    ]);
                }
            } else {
                copy($realPath, $fullDestinationPath);
            }

            $sha256 = hash_file('sha256', $fullDestinationPath);
            $finalSize = filesize($fullDestinationPath);

            ReportAttachment::create([
                'report_id' => $report->id,
                'uploaded_by' => auth()->id() ?? $report->reporter_id,
                'original_name' => $originalName,
                'stored_name' => $diskFileName,
                'mime' => $realMime,
                'size' => $finalSize,
                'sha256' => $sha256,
                'disk_path' => $relativeDiskPath,
            ]);
        }
    }
}
