<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompetitionSettingResource\Pages;
use App\Models\CompetitionSetting;
use App\Services\CompetitionSettings;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class CompetitionSettingResource extends Resource
{
    protected static ?string $model = CompetitionSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan & Sistem';

    protected static ?string $navigationLabel = 'Pengaturan Lomba';

    protected static ?string $modelLabel = 'Pengaturan Lomba';

    protected static ?string $pluralModelLabel = 'Pengaturan Lomba';

    protected static ?int $navigationSort = 1;

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
        return parent::getEloquentQuery()->with('updatedBy');
    }

    public static function table(Table $table): Table
    {
        $settingsService = app(CompetitionSettings::class);

        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Kunci Pengaturan')
                    ->fontFamily(FontFamily::Mono)
                    ->description(fn (CompetitionSetting $record): string => static::getKeyDescription($record->key))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('current_value')
                    ->label('Nilai Saat Ini')
                    ->getStateUsing(function (CompetitionSetting $record) use ($settingsService) {
                        $val = $settingsService->get($record->key);
                        if (is_array($val)) {
                            return json_encode($val, JSON_UNESCAPED_SLASHES);
                        }
                        if (is_bool($val)) {
                            return $val ? 'true (aktif)' : 'false (nonaktif)';
                        }
                        if ($val === null) {
                            return '(null / tidak terbatas)';
                        }
                        if (in_array($record->key, CompetitionSettings::DATE_KEYS, true)) {
                            return $val ? \Illuminate\Support\Carbon::parse($val)->format('d M Y, H:i') . ' WIB' : '(belum diatur)';
                        }
                        return (string) $val;
                    })
                    ->badge(fn (CompetitionSetting $record): bool => in_array($record->key, ['leaderboard_mode', 'rules_version'], true))
                    ->color(fn (CompetitionSetting $record): string => match ($record->key) {
                        'leaderboard_mode' => 'info',
                        'rules_version' => 'warning',
                        default => 'gray',
                    })
                    ->wrap(),

                TextColumn::make('default_value')
                    ->label('Default Resmi')
                    ->getStateUsing(function (CompetitionSetting $record) use ($settingsService) {
                        $def = $settingsService->getDefault($record->key);
                        if (is_array($def)) {
                            return json_encode($def, JSON_UNESCAPED_SLASHES);
                        }
                        if (is_bool($def)) {
                            return $def ? 'true' : 'false';
                        }
                        if ($def === null) {
                            return 'null';
                        }
                        return (string) $def;
                    })
                    ->fontFamily(FontFamily::Mono)
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable(),

                TextColumn::make('updatedBy.name')
                    ->label('Oleh')
                    ->placeholder('Sistem / Awal')
                    ->toggleable(),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Ubah')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->requiresConfirmation(fn (CompetitionSetting $record): bool => in_array($record->key, ['rules_version', 'allowed_email_domains'], true))
                    ->modalHeading(fn (CompetitionSetting $record): string => match ($record->key) {
                        'rules_version' => 'Peringatan: Perubahan Versi Aturan (rules_version)',
                        'allowed_email_domains' => 'Peringatan: Perubahan Domain Email (allowed_email_domains)',
                        default => "Ubah Pengaturan: {$record->key}",
                    })
                    ->modalDescription(fn (CompetitionSetting $record): ?string => match ($record->key) {
                        'rules_version' => 'PERINGATAN DAMPAK: Menaikkan atau mengubah versi aturan lomba akan memaksa SEMUA peserta terdaftar untuk membaca dan menyetujui ulang aturan lomba sebelum dapat melanjutkan aktivitas di platform.',
                        'allowed_email_domains' => 'PERINGATAN DAMPAK: Jika daftar domain email dikosongkan ([]), sistem pendaftaran akan menerima registrasi dari SEMUA domain email publik tanpa batasan kampus.',
                        default => null,
                    })
                    ->form(function (CompetitionSetting $record) use ($settingsService) {
                        $key = $record->key;
                        $current = $settingsService->get($key);

                        if ($key === 'rules_version') {
                            return [
                                TextInput::make('value')
                                    ->label('Versi Aturan Lomba')
                                    ->helperText('Contoh: 1, 2, 2.0. Menaikkan nilai ini memaksa semua peserta setuju ulang.')
                                    ->default($current)
                                    ->required(),
                            ];
                        }

                        if ($key === 'nda_template_text') {
                            return [
                                Textarea::make('value')
                                    ->label('Teks Klausul Template NDA')
                                    ->helperText('Teks khusus klausul surat perjanjian NDA yang dicetak pada PDF yang diunduh peserta (kosongkan untuk default).')
                                    ->rows(8)
                                    ->default($current ?? ''),
                            ];
                        }

                        if ($key === 'allowed_email_domains') {
                            return [
                                TagsInput::make('value')
                                    ->label('Daftar Domain Email yang Diizinkan')
                                    ->helperText('Contoh: ubbg.ac.id. Kosongkan jika ingin menerima semua domain email.')
                                    ->default(is_array($current) ? $current : []),
                            ];
                        }

                        if ($key === 'leaderboard_mode') {
                            return [
                                Select::make('value')
                                    ->label('Mode Papan Peringkat')
                                    ->options([
                                        'visible' => 'Visible (Terlihat Terbuka)',
                                        'hidden' => 'Hidden (Disembunyikan Sepenuhnya)',
                                        'hidden_until_end' => 'Hidden Until End (Terbuka Otomatis Setelah Batas Pengiriman)',
                                    ])
                                    ->default($current)
                                    ->required(),
                            ];
                        }

                        if ($key === 'diminishing_enabled') {
                            return [
                                Toggle::make('value')
                                    ->label('Aktifkan Diminishing Return Poin')
                                    ->default((bool) $current),
                            ];
                        }

                        if (in_array($key, CompetitionSettings::DATE_KEYS, true)) {
                            $val = is_array($current) ? ($current['value'] ?? null) : $current;
                            return [
                                TextInput::make('value')
                                    ->label('Waktu Batas (WIB)')
                                    ->placeholder('YYYY-MM-DD HH:MM:SS')
                                    ->helperText('Masukkan dalam zona WIB (Asia/Jakarta).')
                                    ->default($val ? \Illuminate\Support\Carbon::parse($val)->format('Y-m-d H:i:s') : ''),
                            ];
                        }

                        if (in_array($key, ['points_per_severity', 'triage_weights'], true)) {
                            return [
                                KeyValue::make('value')
                                    ->label('Bobot Poin per Severity')
                                    ->keyLabel('Severity')
                                    ->valueLabel('Poin / Bobot')
                                    ->default(is_array($current) ? $current : []),
                            ];
                        }

                        if ($key === 'diminishing_steps') {
                            return [
                                TagsInput::make('value')
                                    ->label('Faktor Pengali Diminishing')
                                    ->helperText('Contoh: 1.0, 0.5, 0.25')
                                    ->default(is_array($current) ? array_map('strval', $current) : []),
                            ];
                        }

                        // Default numeric / string
                        return [
                            TextInput::make('value')
                                ->label('Nilai Pengaturan')
                                ->default($current === null ? '' : (string) $current)
                                ->helperText("Tipe default: " . gettype($settingsService->getDefault($key))),
                        ];
                    })
                    ->action(function (CompetitionSetting $record, array $data) use ($settingsService) {
                        $key = $record->key;
                        $raw = $data['value'] ?? null;
                        $processed = static::parseValueFromForm($key, $raw, $settingsService);

                        try {
                            // Satu sumber validasi: pakai class CompetitionSettings yang sama!
                            $settingsService->validateKey($key, $processed);
                        } catch (InvalidArgumentException $e) {
                            Notification::make()
                                ->title('Validasi Pengaturan Gagal')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $settingsService->set($key, $processed, auth()->id());

                        Notification::make()
                            ->title('Pengaturan Berhasil Disimpan')
                            ->body("Kunci {$key} telah diperbarui dan cache langsung di-refresh.")
                            ->success()
                            ->send();
                    }),

                Action::make('reset')
                    ->label('Reset Default')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(fn (CompetitionSetting $record): string => "Reset {$record->key} ke Default?")
                    ->modalDescription(function (CompetitionSetting $record) use ($settingsService): string {
                        $def = $settingsService->getDefault($record->key);
                        $formatted = is_array($def) ? json_encode($def) : (is_bool($def) ? ($def ? 'true' : 'false') : (string) $def);
                        return "Nilai saat ini akan dikembalikan ke nilai default resmi: {$formatted}. Perubahan akan dicatat di log aktivitas.";
                    })
                    ->action(function (CompetitionSetting $record) use ($settingsService) {
                        $settingsService->reset($record->key, auth()->id());

                        Notification::make()
                            ->title('Pengaturan Direset')
                            ->body("Kunci {$record->key} telah dikembalikan ke nilai default.")
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([25, 50, 'all'])
            ->defaultPaginationPageOption(50);
    }

    public static function parseValueFromForm(string $key, mixed $raw, CompetitionSettings $settings): mixed
    {
        if ($key === 'diminishing_enabled') {
            return (bool) $raw;
        }

        if ($key === 'allowed_email_domains') {
            if (is_array($raw)) {
                return array_values(array_filter(array_map('trim', $raw)));
            }
            if (is_string($raw) && trim($raw) !== '') {
                return array_values(array_filter(array_map('trim', explode(',', $raw))));
            }
            return [];
        }

        if ($key === 'diminishing_steps') {
            if (is_array($raw)) {
                return array_map('floatval', $raw);
            }
            return [1.0, 0.5, 0.25];
        }

        if (in_array($key, ['points_per_severity', 'triage_weights'], true)) {
            if (is_array($raw)) {
                $casted = [];
                foreach ($raw as $k => $v) {
                    $casted[$k] = is_numeric($v) ? (int) $v : $v;
                }
                return $casted;
            }
            return $raw;
        }

        if (in_array($key, CompetitionSettings::DATE_KEYS, true)) {
            if ($raw === null || trim((string) $raw) === '') {
                return null;
            }
            return trim((string) $raw);
        }

        if (in_array($key, [
            'max_active_reports_per_team',
            'min_team_size',
            'max_team_size',
            'registration_rate_limit_per_hour',
            'report_rate_limit_per_hour',
            'upload_rate_limit_per_minute',
            'comment_rate_limit_per_minute',
        ], true)) {
            return is_numeric($raw) ? (int) $raw : $raw;
        }

        if ($key === 'max_total_reports_per_team' || $key === 'nda_template_text') {
            if ($raw === null || trim((string) $raw) === '') {
                return null;
            }
            return $key === 'max_total_reports_per_team' ? (is_numeric($raw) ? (int) $raw : $raw) : trim((string) $raw);
        }

        if ($key === 'duplicate_points') {
            return is_numeric($raw) ? (int) $raw : $raw;
        }

        return $raw;
    }

    public static function getKeyDescription(string $key): string
    {
        return match ($key) {
            'max_active_reports_per_team' => 'Batas maksimal laporan aktif per tim pada satu waktu (1-50)',
            'max_total_reports_per_team' => 'Batas total laporan keseluruhan per tim (null = tidak terbatas)',
            'min_team_size' => 'Jumlah minimal anggota dalam tim (1-10)',
            'max_team_size' => 'Jumlah maksimal anggota dalam tim (1-10)',
            'registration_open_at' => 'Waktu pembukaan pendaftaran lomba (WIB)',
            'registration_close_at' => 'Waktu penutupan pendaftaran lomba (WIB)',
            'submission_open_at' => 'Waktu pembukaan pengiriman laporan bug (WIB)',
            'submission_close_at' => 'Waktu penutupan pengiriman laporan bug (WIB)',
            'points_per_severity' => 'Poin dasar reward untuk masing-masing tingkat keparahan',
            'duplicate_points' => 'Poin yang diberikan untuk laporan yang ditandai duplikat',
            'diminishing_enabled' => 'Apakah poin berkurang untuk bug tipe sama berikutnya',
            'diminishing_steps' => 'Faktor pengali pengurang poin bertahap',
            'leaderboard_mode' => 'Mode tampilan papan peringkat (visible, hidden, hidden_until_end)',
            'triage_weights' => 'Bobot prioritas antrian triage per severity',
            'triage_age_factor' => 'Faktor pengali umur laporan pada prioritas antrian triage',
            'rules_version' => 'Versi aturan lomba (jika dinaikkan, seluruh peserta wajib setuju ulang)',
            'nda_template_version' => 'Nomor versi template surat perjanjian NDA yang dicetak pada dokumen PDF',
            'nda_template_text' => 'Teks klausul khusus template NDA (kosongkan / null untuk menggunakan teks default)',
            'allowed_email_domains' => 'Daftar domain email resmi yang diizinkan (kosong = semua domain)',
            'registration_rate_limit_per_hour' => 'Batas laju registrasi per jam',
            'report_rate_limit_per_hour' => 'Batas pengiriman laporan per jam per tim',
            'upload_rate_limit_per_minute' => 'Batas upload berkas per menit',
            'comment_rate_limit_per_minute' => 'Batas pengiriman komentar per menit',
            default => 'Konfigurasi parameter perlombaan',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompetitionSettings::route('/'),
        ];
    }
}
