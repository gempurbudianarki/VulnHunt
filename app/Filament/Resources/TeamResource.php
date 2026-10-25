<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Models\Team;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $navigationLabel = 'Tim Peserta';

    protected static ?string $modelLabel = 'Tim Peserta';

    protected static ?string $pluralModelLabel = 'Daftar Tim Peserta';

    protected static ?int $navigationSort = 6;

    public static function canCreate(): bool
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
            ->with(['leader', 'members.user'])
            ->withCount(['members', 'reports']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Tim')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invite_code')
                    ->label('Kode Undangan')
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->searchable(),

                TextColumn::make('leader.name')
                    ->label('Ketua Tim')
                    ->searchable(),

                TextColumn::make('members_count')
                    ->label('Anggota')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status Tim')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'disqualified' => 'Didiskualifikasi',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'disqualified' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('report_quota_override')
                    ->label('Override Kuota')
                    ->placeholder('Default')
                    ->formatStateUsing(fn ($state) => $state !== null ? "{$state} Laporan" : 'Default')
                    ->sortable(),

                TextColumn::make('reports_count')
                    ->label('Laporan')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'disqualified' => 'Didiskualifikasi',
                    ]),
            ])
            ->actions([
                Action::make('toggleDisqualify')
                    ->label(fn (Team $record): string => $record->status === 'disqualified' ? 'Pulihkan Tim' : 'Diskualifikasi')
                    ->icon(fn (Team $record): string => $record->status === 'disqualified' ? 'heroicon-o-arrow-path' : 'heroicon-o-shield-exclamation')
                    ->color(fn (Team $record): string => $record->status === 'disqualified' ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Team $record): string => ($record->status === 'disqualified' ? 'Pulihkan Status Tim ' : 'Diskualifikasi Tim ').$record->name)
                    ->modalDescription(fn (Team $record): string => $record->status === 'disqualified'
                        ? 'Tim akan kembali aktif dan dapat melanjutkan kompetisi.'
                        : 'Tim yang didiskualifikasi tidak akan dapat mengirimkan laporan baru dan namanya akan ditandai pada peringkat.')
                    ->action(function (Team $record) {
                        $newStatus = $record->status === 'disqualified' ? 'active' : 'disqualified';
                        $record->update(['status' => $newStatus]);

                        Notification::make()
                            ->title('Status Tim Diperbarui')
                            ->body("Tim {$record->name} sekarang berstatus: {$newStatus}")
                            ->success()
                            ->send();
                    }),

                Action::make('overrideQuota')
                    ->label('Atur Kuota')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->form([
                        TextInput::make('report_quota_override')
                            ->label('Batas Kuota Laporan Aktif')
                            ->helperText('Kosongkan untuk mengembalikan ke kuota default kompetisi.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->default(fn (Team $record) => $record->report_quota_override),
                    ])
                    ->action(function (Team $record, array $data) {
                        $value = ! empty($data['report_quota_override']) ? (int) $data['report_quota_override'] : null;
                        $record->update(['report_quota_override' => $value]);

                        Notification::make()
                            ->title('Kuota Laporan Tim Diperbarui')
                            ->body($value !== null ? "Kuota aktif tim {$record->name} diset ke {$value} laporan." : "Kuota tim {$record->name} dikembalikan ke default kompetisi.")
                            ->success()
                            ->send();
                    }),

                Action::make('reassignLeader')
                    ->label('Pindah Ketua')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->form(fn (Team $record): array => [
                        Select::make('new_leader_id')
                            ->label('Pilih Anggota sebagai Ketua Baru')
                            ->options(
                                $record->members()
                                    ->with('user')
                                    ->get()
                                    ->filter(fn ($m) => $m->user_id !== $record->leader_id)
                                    ->mapWithKeys(fn ($m) => [$m->user_id => "{$m->user->name} ({$m->user->email})"])
                            )
                            ->required()
                            ->helperText('Hanya anggota tim saat ini yang dapat dijadikan ketua baru.'),
                    ])
                    ->action(function (Team $record, array $data) {
                        $newLeader = User::findOrFail($data['new_leader_id']);
                        $record->reassignLeader($newLeader);

                        Notification::make()
                            ->title('Ketua Tim Berhasil Dipindahkan')
                            ->body("Ketua tim {$record->name} sekarang adalah {$newLeader->name}.")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
        ];
    }
}
