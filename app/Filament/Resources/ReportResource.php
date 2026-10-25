<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use App\Services\ReportStateMachine;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationGroup = 'Triage & Penilaian';

    protected static ?string $navigationLabel = 'Laporan Kerentanan';

    protected static ?string $modelLabel = 'Laporan Kerentanan';

    protected static ?string $pluralModelLabel = 'Antrean Triage Laporan';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['team', 'scope', 'vulnerabilityType', 'reporter'])
            ->orderByRaw("CASE status WHEN 'submitted' THEN 1 WHEN 'in_review' THEN 2 WHEN 'needs_info' THEN 3 WHEN 'valid' THEN 4 WHEN 'duplicate' THEN 5 WHEN 'invalid' THEN 6 WHEN 'rewarded' THEN 7 ELSE 8 END");

        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $query->orderByRaw("(CASE severity_claimed WHEN 'critical' THEN 40 WHEN 'high' THEN 25 WHEN 'medium' THEN 10 WHEN 'low' THEN 5 ELSE 1 END + (TIMESTAMPDIFF(HOUR, submitted_at, NOW()) * 0.5)) DESC");
        } else {
            $query->orderByRaw("(CASE severity_claimed WHEN 'critical' THEN 40 WHEN 'high' THEN 25 WHEN 'medium' THEN 10 WHEN 'low' THEN 5 ELSE 1 END) DESC");
        }

        return $query->orderBy('submitted_at', 'asc');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Laporan')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('team.name')
                    ->label('Tim')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul Temuan')
                    ->limit(35)
                    ->searchable(),

                Tables\Columns\TextColumn::make('scope.name')
                    ->label('Scope / Target')
                    ->sortable(),

                Tables\Columns\TextColumn::make('vulnerabilityType.cwe_id')
                    ->label('CWE')
                    ->fontFamily(FontFamily::Mono)
                    ->sortable(),

                Tables\Columns\TextColumn::make('severity_claimed')
                    ->label('Klaim')
                    ->badge()
                    ->colors([
                        'gray' => 'info',
                        'success' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ]),

                Tables\Columns\TextColumn::make('severity_final')
                    ->label('Final')
                    ->badge()
                    ->placeholder('-')
                    ->colors([
                        'gray' => 'info',
                        'success' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'critical',
                    ]),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'gray' => 'submitted',
                        'warning' => 'in_review',
                        'danger' => 'needs_info',
                        'success' => 'valid',
                        'danger' => 'invalid',
                        'info' => 'duplicate',
                        'primary' => 'rewarded',
                    ]),

                Tables\Columns\TextColumn::make('points_awarded')
                    ->label('Poin')
                    ->sortable()
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('triage_priority')
                    ->label('Prioritas')
                    ->state(function (Report $record): string {
                        $weights = ['critical' => 40, 'high' => 25, 'medium' => 10, 'low' => 5, 'info' => 1];
                        $base = $weights[$record->severity_claimed] ?? 1;
                        $refTime = $record->updated_at ?? $record->submitted_at;
                        $hours = $refTime ? max(0, now()->diffInHours($refTime)) : 0;
                        $priority = $base + ($hours * 0.5);
                        return number_format($priority, 1);
                    })
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Waktu Masuk')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'submitted' => 'Terkirim (Submitted)',
                        'in_review' => 'Dalam Peninjauan (In Review)',
                        'needs_info' => 'Perlu Info (Needs Info)',
                        'valid' => 'Valid',
                        'invalid' => 'Tidak Valid (Invalid)',
                        'duplicate' => 'Duplikat',
                        'rewarded' => 'Diberi Hadiah (Rewarded)',
                    ]),

                SelectFilter::make('severity_claimed')
                    ->label('Tingkat Keparahan (Klaim)')
                    ->options([
                        'critical' => 'Critical',
                        'high' => 'High',
                        'medium' => 'Medium',
                        'low' => 'Low',
                        'info' => 'Info',
                    ]),

                SelectFilter::make('scope_id')
                    ->label('Scope')
                    ->relationship('scope', 'name'),

                SelectFilter::make('vulnerability_type_id')
                    ->label('Jenis Kerentanan (CWE)')
                    ->relationship('vulnerabilityType', 'name'),

                SelectFilter::make('team_id')
                    ->label('Tim (Ekspedisi)')
                    ->relationship('team', 'name'),

                Tables\Filters\Filter::make('submitted_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('submitted_from')->label('Tanggal Masuk Mulai'),
                        \Filament\Forms\Components\DatePicker::make('submitted_until')->label('Tanggal Masuk Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['submitted_from'], fn (Builder $query, $date) => $query->whereDate('submitted_at', '>=', $date))
                            ->when($data['submitted_until'], fn (Builder $query, $date) => $query->whereDate('submitted_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Triage'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_in_review')
                        ->label('Tandai In Review (Aksi Massal)')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $stateMachine = app(ReportStateMachine::class);
                            $actor = auth()->user();
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'submitted') {
                                    $stateMachine->transition($record, 'in_review', $actor, [
                                        'reason' => 'Aksi massal triage oleh staf.',
                                    ]);
                                    $count++;
                                }
                            }
                            Notification::make()
                                ->success()
                                ->title("{$count} laporan berhasil ditandai In Review.")
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Ringkasan Spesimen Laporan')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('code')->label('Nomor Koleksi (Kode)')->fontFamily(FontFamily::Mono)->weight('bold')->copyable(),
                            TextEntry::make('status')->label('Status')->badge()->colors([
                                'gray' => 'submitted',
                                'warning' => 'in_review',
                                'danger' => 'needs_info',
                                'success' => 'valid',
                                'danger' => 'invalid',
                                'info' => 'duplicate',
                                'primary' => 'rewarded',
                            ]),
                            TextEntry::make('points_awarded')->label('Poin Diperoleh')->weight('bold'),
                            TextEntry::make('team.name')->label('Ekspedisi (Tim)')->weight('semibold'),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('scope.name')->label('Habitat (Scope)'),
                            TextEntry::make('endpoint')->label('Endpoint Target')->fontFamily(FontFamily::Mono),
                            TextEntry::make('vulnerabilityType.cwe_id')->label('Taksonomi (CWE)')->fontFamily(FontFamily::Mono),
                            TextEntry::make('reporter.name')->label('Penemu (Pelapor)'),
                        ]),
                        Grid::make(4)->schema([
                            TextEntry::make('severity_claimed')->label('Severity (Klaim)')->badge(),
                            TextEntry::make('cvss_score_claimed')->label('Skor CVSS Klaim')->fontFamily(FontFamily::Mono),
                            TextEntry::make('severity_final')->label('Severity (Final)')->badge()->placeholder('-'),
                            TextEntry::make('cvss_score_final')->label('Skor CVSS Final')->fontFamily(FontFamily::Mono)->placeholder('-'),
                        ]),
                        Grid::make(2)->schema([
                            TextEntry::make('cvss_vector_claimed')->label('Vector CVSS Diajukan')->fontFamily(FontFamily::Mono)->copyable(),
                            TextEntry::make('cvss_vector_final')->label('Vector CVSS Final')->fontFamily(FontFamily::Mono)->placeholder('-')->copyable(),
                        ]),
                    ]),

                Section::make('Muatan Teknis Celah Keamanan (Dekripsi Server)')
                    ->description('Kolom sensitif berikut tersimpan dalam bentuk ciphertext terenkripsi di database dan didekripsi untuk peninjauan triager.')
                    ->schema([
                        TextEntry::make('title')->label('Judul Temuan')->weight('bold'),
                        TextEntry::make('description')->label('Deskripsi Temuan')->prose(),
                        TextEntry::make('steps_to_reproduce')->label('Langkah Reproduksi (Proof of Concept)')->fontFamily(FontFamily::Mono)->prose(),
                        TextEntry::make('impact')->label('Dampak Potensial (Impact)')->prose(),
                        TextEntry::make('remediation')->label('Saran Mitigasi / Perbaikan')->prose()->placeholder('Tidak ada saran perbaikan yang dicantumkan.'),
                    ]),

                Section::make('Berkas Lampiran Bukti Eksploitasi')
                    ->schema([
                        RepeatableEntry::make('attachments')
                            ->label('Lampiran Tersimpan')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('original_name')->label('Nama Berkas'),
                                    TextEntry::make('size')->label('Ukuran')->formatStateUsing(fn ($state) => round($state / 1024, 1).' KB'),
                                    TextEntry::make('mime')->label('Tipe MIME (finfo)'),
                                    TextEntry::make('sha256')->label('Hash SHA-256')->fontFamily(FontFamily::Mono)->limit(20)->copyable(),
                                ]),
                            ]),
                    ]),

                Section::make('Komunikasi & Catatan Laporan')
                    ->description('Termasuk catatan internal staf yang tersembunyi dari portal dan notifikasi peserta.')
                    ->schema([
                        RepeatableEntry::make('comments')
                            ->label('Daftar Catatan & Komentar')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextEntry::make('user.name')->label('Penulis')->weight('bold'),
                                    TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                                    TextEntry::make('is_internal')->label('Visibilitas')->badge()->formatStateUsing(fn ($state) => $state ? 'INTERNAL STAF (Rahasia)' : 'PUBLIK TIM')->colors([
                                        'danger' => fn ($state) => $state,
                                        'success' => fn ($state) => ! $state,
                                    ]),
                                ]),
                                TextEntry::make('body')->label('Isi Pesan')->prose(),
                            ]),
                    ]),

                Section::make('Linimasa Riwayat Status & Audit Penilaian Poin')
                    ->schema([
                        RepeatableEntry::make('statusHistories')
                            ->label('Riwayat Transisi')
                            ->schema([
                                Grid::make(4)->schema([
                                    TextEntry::make('from_status')->label('Dari Status')->badge(),
                                    TextEntry::make('to_status')->label('Ke Status')->badge(),
                                    TextEntry::make('changedBy.name')->label('Diubah Oleh')->weight('semibold'),
                                    TextEntry::make('created_at')->label('Waktu Transisi')->dateTime('d M Y H:i:s'),
                                ]),
                                TextEntry::make('note')->label('Catatan Audit & Rincian Perhitungan')->prose(),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'view' => Pages\ViewReport::route('/{record}'),
        ];
    }
}
