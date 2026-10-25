<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    protected static ?string $navigationLabel = 'Peserta & Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Daftar Peserta & Pengguna';

    protected static ?int $navigationSort = 5;

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
            ->with(['roles', 'teamMember.team']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->fontFamily(FontFamily::Mono),

                TextColumn::make('nim')
                    ->label('NIM')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('study_program')
                    ->label('Program Studi')
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('teamMember.team.name')
                    ->label('Tim')
                    ->placeholder('Belum bergabung')
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'triager' => 'warning',
                        'participant' => 'info',
                        default => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Filter Peran')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Action::make('toggleActive')
                    ->label(fn (User $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record): string => $record->is_active ? 'heroicon-o-no-symbol' : 'heroicon-o-check')
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => ($record->is_active ? 'Nonaktifkan Akun ' : 'Aktifkan Akun ').$record->name)
                    ->modalDescription(fn (User $record): string => $record->is_active
                        ? 'Pengguna yang dinonaktifkan tidak akan dapat masuk (login) ke platform.'
                        : 'Pengguna akan dapat masuk kembali ke platform.')
                    ->action(function (User $record) {
                        if ($record->id === auth()->id()) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Anda tidak dapat menonaktifkan akun Anda sendiri.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'is_active' => ! $record->is_active,
                        ]);

                        Notification::make()
                            ->title('Status Pengguna Diperbarui')
                            ->body("Akun {$record->name} sekarang berstatus ".($record->is_active ? 'Aktif' : 'Non-Aktif'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
