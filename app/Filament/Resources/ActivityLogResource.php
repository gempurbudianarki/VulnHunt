<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Audit & Keamanan';

    protected static ?string $navigationLabel = 'Log Aktivitas';

    protected static ?string $modelLabel = 'Log Aktivitas';

    protected static ?string $pluralModelLabel = 'Catatan Log Aktivitas';

    protected static ?int $navigationSort = 10;

    /**
     * Resource baca-saja murni: cegah create, edit, dan delete.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

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

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('causer')->latest('id');
    }

    /**
     * Saring dan samarkan seluruh data sensitif (password, 2fa secret, token, recovery codes).
     */
    public static function sanitizeProperties(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $sensitiveKeys = [
            'password',
            'secret',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'token',
            'recovery_codes',
            'remember_token',
            'private_key',
            'cookie',
            'authorization',
        ];

        $clean = [];
        foreach ($data as $key => $value) {
            $isSensitive = false;
            foreach ($sensitiveKeys as $pattern) {
                if (stripos((string) $key, $pattern) !== false) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $clean[$key] = '[TERLINDUNGI / REDACTED]';
            } elseif (is_array($value)) {
                $clean[$key] = static::sanitizeProperties($value);
            } else {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Rincian Log Audit')
                    ->schema([
                        TextInput::make('created_at')
                            ->label('Waktu Kejadian (WIB)')
                            ->formatStateUsing(fn ($state) => $state ? \Illuminate\Support\Carbon::parse($state)->timezone('Asia/Jakarta')->format('d M Y, H:i:s') . ' WIB' : '-'),

                        TextInput::make('log_name')
                            ->label('Modul / Kategori'),

                        TextInput::make('description')
                            ->label('Aktivitas')
                            ->columnSpanFull(),

                        TextInput::make('causer_info')
                            ->label('Pelaku')
                            ->formatStateUsing(function (Activity $record): string {
                                if ($record->causer) {
                                    return $record->causer->name . ' (' . $record->causer->email . ')';
                                }
                                return 'Sistem / Anonim';
                            }),

                        TextInput::make('subject_info')
                            ->label('Objek Terkait')
                            ->formatStateUsing(function (Activity $record): string {
                                if ($record->subject_type) {
                                    return class_basename($record->subject_type) . ' #' . $record->subject_id;
                                }
                                return '-';
                            }),

                        Textarea::make('sanitized_properties')
                            ->label('Metadata & Properti (Data Sensitif Disamarkan)')
                            ->rows(6)
                            ->formatStateUsing(fn (Activity $record) => json_encode(static::sanitizeProperties($record->properties->toArray()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu (WIB)')
                    ->dateTime('d M Y, H:i:s')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),

                TextColumn::make('log_name')
                    ->label('Modul')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nda' => 'warning',
                        'report' => 'danger',
                        'competition_settings' => 'info',
                        'export' => 'success',
                        'auth' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Deskripsi Aktivitas')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('causer.name')
                    ->label('Pelaku')
                    ->placeholder('Sistem / Anonim')
                    ->searchable(),

                TextColumn::make('subject_type')
                    ->label('Objek')
                    ->formatStateUsing(fn (?string $state, Activity $record) => $state ? class_basename($state) . ' #' . $record->subject_id : '-')
                    ->toggleable(),

                TextColumn::make('sanitized_json')
                    ->label('Rincian Data')
                    ->getStateUsing(fn (Activity $record) => json_encode(static::sanitizeProperties($record->properties->toArray()), JSON_UNESCAPED_SLASHES))
                    ->fontFamily(FontFamily::Mono)
                    ->wrap(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Filter Modul')
                    ->options([
                        'nda' => 'Verifikasi NDA',
                        'report' => 'Laporan Bug',
                        'competition_settings' => 'Pengaturan Lomba',
                        'export' => 'Ekspor Data CSV',
                        'auth' => 'Otentikasi & Keamanan',
                    ]),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading('Rincian Audit Log Aktivitas'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }
}
