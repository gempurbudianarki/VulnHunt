<?php

namespace App\Filament\Resources\ReportResource\Pages;

use App\Filament\Resources\ReportResource;
use App\Models\Report;
use App\Models\ReportComment;
use App\Services\ReportStateMachine;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // 1. Mulai Peninjauan (submitted -> in_review)
            Actions\Action::make('start_review')
                ->label('Mulai Review (In Review)')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'submitted')
                ->requiresConfirmation()
                ->action(function () {
                    app(ReportStateMachine::class)->transition(
                        $this->record,
                        'in_review',
                        auth()->user(),
                        ['reason' => 'Staf mulai memeriksa laporan temuan.']
                    );
                    $this->refreshFormData(['status', 'triaged_by', 'triaged_at']);
                    Notification::make()->success()->title('Status laporan berhasil diubah ke In Review.')->send();
                }),

            // 2. Minta Informasi Tambahan (in_review -> needs_info)
            Actions\Action::make('request_info')
                ->label('Minta Info Tambahan')
                ->icon('heroicon-o-question-mark-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'in_review')
                ->form([
                    Textarea::make('reason')
                        ->label('Pesan Klarifikasi / Pertanyaan untuk Tim')
                        ->placeholder('Tuliskan langkah reproduksi yang belum jelas atau bukti tambahan yang diperlukan...')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    app(ReportStateMachine::class)->transition(
                        $this->record,
                        'needs_info',
                        auth()->user(),
                        ['reason' => $data['reason']]
                    );
                    $this->refreshFormData(['status']);
                    Notification::make()->success()->title('Status diubah ke Needs Info dan pertanyaan dikirim ke tim.')->send();
                }),

            // 3. Tetapkan Laporan Valid (in_review -> valid)
            Actions\Action::make('mark_valid')
                ->label('Tetapkan VALID')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn () => $this->record->status === 'in_review')
                ->form([
                    TextInput::make('cvss_vector_final')
                        ->label('Vector CVSS Final')
                        ->default(fn () => $this->record->cvss_vector_final ?: $this->record->cvss_vector_claimed)
                        ->required()
                        ->helperText('Contoh: CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H'),

                    TextInput::make('quality_factor')
                        ->label('Faktor Kualitas Laporan (0.50 s/d 1.20)')
                        ->numeric()
                        ->default(fn () => $this->record->quality_factor ? (float) $this->record->quality_factor : 1.00)
                        ->minValue(0.50)
                        ->maxValue(1.20)
                        ->step(0.05)
                        ->required()
                        ->helperText('Nilai pengali kualitas PoC dan saran perbaikan (default: 1.00).'),

                    Textarea::make('reason')
                        ->label('Catatan Keputusan / Alasan Perubahan CVSS')
                        ->placeholder('Wajib diisi jika vector CVSS final berbeda dari yang diklaim peserta...')
                        ->rows(2),

                    TextInput::make('points_override')
                        ->label('Override Poin Manual (Opsional)')
                        ->numeric()
                        ->nullable()
                        ->helperText('Kosongkan untuk menggunakan kalkulasi poin otomatis.'),

                    TextInput::make('points_override_note')
                        ->label('Catatan Alasan Override Poin')
                        ->placeholder('Wajib diisi jika poin manual ditetapkan...')
                        ->nullable(),
                ])
                ->action(function (array $data) {
                    app(ReportStateMachine::class)->transition(
                        $this->record,
                        'valid',
                        auth()->user(),
                        [
                            'cvss_vector_final' => $data['cvss_vector_final'],
                            'quality_factor' => (float) $data['quality_factor'],
                            'reason' => $data['reason'] ?? null,
                            'points_override' => filled($data['points_override'] ?? null) ? (int) $data['points_override'] : null,
                            'points_override_note' => $data['points_override_note'] ?? null,
                        ]
                    );
                    $this->refreshFormData(['status', 'points_awarded', 'cvss_vector_final', 'cvss_score_final', 'severity_final', 'closed_at']);
                    Notification::make()->success()->title('Laporan berhasil ditetapkan VALID dan poin telah dihitung!')->send();
                }),

            // 4. Tolak Laporan (in_review -> invalid)
            Actions\Action::make('mark_invalid')
                ->label('Tolak (Invalid)')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $this->record->status === 'in_review')
                ->form([
                    Textarea::make('reason')
                        ->label('Alasan Penolakan (Wajib)')
                        ->placeholder('Jelaskan mengapa temuan ini tidak valid (misal: false positive, out of scope, intended behavior)...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    app(ReportStateMachine::class)->transition(
                        $this->record,
                        'invalid',
                        auth()->user(),
                        ['reason' => $data['reason']]
                    );
                    $this->refreshFormData(['status', 'points_awarded', 'closed_at']);
                    Notification::make()->success()->title('Laporan ditolak (Invalid).')->send();
                }),

            // 5. Tandai Duplikat (in_review -> duplicate)
            Actions\Action::make('mark_duplicate')
                ->label('Tandai Duplikat')
                ->icon('heroicon-o-document-duplicate')
                ->color('info')
                ->visible(fn () => $this->record->status === 'in_review')
                ->form([
                    Select::make('duplicate_of')
                        ->label('Pilih Laporan Asli (duplicate_of)')
                        ->options(function () {
                            return Report::where('id', '!=', $this->record->id)
                                ->orderBy('submitted_at', 'asc')
                                ->get()
                                ->mapWithKeys(fn ($r) => [
                                    $r->id => "{$r->code} - {$r->title} ({$r->status}, Submit: {$r->submitted_at?->format('d/m/Y H:i')})",
                                ]);
                        })
                        ->searchable()
                        ->required(),

                    Textarea::make('reason')
                        ->label('Catatan Duplikasi')
                        ->rows(2),
                ])
                ->action(function (array $data) {
                    app(ReportStateMachine::class)->transition(
                        $this->record,
                        'duplicate',
                        auth()->user(),
                        [
                            'duplicate_of' => (int) $data['duplicate_of'],
                            'reason' => $data['reason'] ?? null,
                        ]
                    );
                    $this->refreshFormData(['status', 'duplicate_of', 'points_awarded', 'closed_at']);
                    Notification::make()->success()->title('Laporan ditandai Duplikat.')->send();
                }),

            // 6. Tandai Rewarded (valid -> rewarded)
            Actions\Action::make('mark_rewarded')
                ->label('Tandai Hadiah Diserahkan (Rewarded)')
                ->icon('heroicon-o-gift')
                ->color('primary')
                ->visible(fn () => $this->record->status === 'valid')
                ->requiresConfirmation()
                ->action(function () {
                    app(ReportStateMachine::class)->transition(
                        $this->record,
                        'rewarded',
                        auth()->user(),
                        ['reason' => 'Bounty/hadiah telah diserahkan kepada tim.']
                    );
                    $this->refreshFormData(['status']);
                    Notification::make()->success()->title('Status laporan diperbarui menjadi Rewarded.')->send();
                }),

            // 7. Buka Kembali Keputusan (valid/invalid/duplicate/rewarded -> in_review)
            Actions\Action::make('reopen_decision')
                ->label('Buka Kembali Keputusan (Reopen)')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn () => in_array($this->record->status, ['valid', 'invalid', 'duplicate', 'rewarded'], true))
                ->form([
                    Textarea::make('reason')
                        ->label('Alasan Membuka Kembali Keputusan (Wajib)')
                        ->placeholder('Jelaskan alasan pembukaan kembali (misal: evaluasi ulang dampak, koreksi status)...')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    app(ReportStateMachine::class)->transition(
                        $this->record,
                        'in_review',
                        auth()->user(),
                        ['reason' => $data['reason']]
                    );
                    $this->refreshFormData(['status', 'points_awarded', 'closed_at']);
                    Notification::make()->success()->title('Keputusan dibuka kembali. Status laporan kembali ke In Review dan poin direset.')->send();
                }),

            // 8. Tambah Komentar (Internal Staf / Publik)
            Actions\Action::make('add_comment')
                ->label('Tambah Komentar')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('gray')
                ->form([
                    Textarea::make('body')
                        ->label('Isi Komentar / Catatan')
                        ->required()
                        ->rows(3),

                    Toggle::make('is_internal')
                        ->label('Catatan Khusus Internal Staf (TIDAK terlihat oleh peserta)')
                        ->default(true),
                ])
                ->action(function (array $data) {
                    ReportComment::create([
                        'report_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'body' => $data['body'],
                        'is_internal' => (bool) $data['is_internal'],
                    ]);
                    Notification::make()->success()->title('Komentar berhasil disimpan.')->send();
                }),
        ];
    }
}
