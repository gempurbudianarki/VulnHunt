<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NdaSubmissionResource\Pages;
use App\Models\NdaSubmission;
use App\Notifications\NdaStatusChangedNotification;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NdaSubmissionResource extends Resource
{
    protected static ?string $model = NdaSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    protected static ?string $navigationGroup = 'Manajemen & Verifikasi';

    protected static ?string $navigationLabel = 'Verifikasi NDA';

    protected static ?string $modelLabel = 'Pengajuan NDA';

    protected static ?string $pluralModelLabel = 'Verifikasi Dokumen NDA';

    protected static ?int $navigationSort = 2;

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
        return parent::getEloquentQuery()
            ->with(['user', 'team', 'reviewer'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'rejected' THEN 2 WHEN 'approved' THEN 3 ELSE 4 END")
            ->latest('submitted_at');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->label('No. Dokumen')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily(FontFamily::Mono),

                TextColumn::make('user.name')
                    ->label('Peserta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.nim')
                    ->label('NIM')
                    ->searchable(),

                TextColumn::make('team.name')
                    ->label('Tim')
                    ->placeholder('Tanpa Tim')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending Review',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('submitted_at')
                    ->label('Waktu Pengajuan')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('reviewer.name')
                    ->label('Diverifikasi Oleh')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter Status')
                    ->options([
                        'pending' => 'Pending Review',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                Action::make('download')
                    ->label('Unduh Berkas')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (NdaSubmission $record): string => route('verification.download', $record->id))
                    ->openUrlInNewTab(),

                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (NdaSubmission $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Persetujuan NDA')
                    ->modalDescription('Apakah Anda yakin dokumen NDA peserta ini valid dan telah bertanda tangan fisik?')
                    ->modalSubmitActionLabel('Ya, Setujui')
                    ->action(function (NdaSubmission $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'reject_reason' => null,
                        ]);

                        $record->user->notify(new NdaStatusChangedNotification($record->document_number, 'approved'));

                        Notification::make()
                            ->title('NDA Berhasil Disetujui')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (NdaSubmission $record): bool => $record->status === 'pending')
                    ->modalHeading('Tolak Pengajuan NDA')
                    ->form([
                        Textarea::make('reject_reason')
                            ->label('Alasan Penolakan')
                            ->helperText('Alasan penolakan wajib diisi dan akan dikirimkan ke email peserta.')
                            ->required()
                            ->minLength(5),
                    ])
                    ->modalSubmitActionLabel('Tolak Pengajuan')
                    ->action(function (NdaSubmission $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_by' => auth()->id(),
                            'reviewed_at' => now(),
                            'reject_reason' => $data['reject_reason'],
                        ]);

                        $record->user->notify(new NdaStatusChangedNotification(
                            $record->document_number,
                            'rejected',
                            $data['reject_reason']
                        ));

                        Notification::make()
                            ->title('Pengajuan NDA Ditolak')
                            ->warning()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNdaSubmissions::route('/'),
        ];
    }
}
