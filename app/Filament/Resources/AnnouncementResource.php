<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Komunikasi & Pengumuman';

    protected static ?string $navigationLabel = 'Pengumuman Lomba';

    protected static ?string $modelLabel = 'Pengumuman';

    protected static ?string $pluralModelLabel = 'Daftar Pengumuman';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('creator');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pengumuman')
                    ->description('Tulis pengumuman resmi untuk seluruh peserta lomba. Format markdown didukung dan disanitasi secara otomatis.')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Pengumuman')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Toggle::make('is_pinned')
                            ->label('Sematkan di Atas (Pinned)')
                            ->helperText('Pengumuman tersemat akan muncul di bagian teratas dasbor dan daftar pengumuman.')
                            ->default(false),

                        DateTimePicker::make('published_at')
                            ->label('Waktu Publikasi (WIB)')
                            ->timezone('Asia/Jakarta')
                            ->helperText('Kosongkan untuk menyimpan sebagai draf, atau atur waktu tertentu dalam zona WIB.')
                            ->nullable(),

                        MarkdownEditor::make('body')
                            ->label('Isi Pengumuman (Markdown)')
                            ->required()
                            ->helperText('Mendukung sintaks Markdown standar. Tag HTML mentah dan tautan berbahaya (javascript:, data:) akan dibersihkan secara otomatis.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->wrap(),

                IconColumn::make('is_pinned')
                    ->label('Tersemat')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Announcement $record): string {
                        if ($record->published_at === null) {
                            return 'Draf';
                        }
                        return $record->published_at->isPast() ? 'Terbit' : 'Terjadwal';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Terbit' => 'success',
                        'Terjadwal' => 'warning',
                        'Draf' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('published_at')
                    ->label('Waktu Terbit (WIB)')
                    ->dateTime('d M Y, H:i')
                    ->timezone('Asia/Jakarta')
                    ->sortable()
                    ->placeholder('(Draf belum terbit)'),

                TextColumn::make('creator.name')
                    ->label('Penulis')
                    ->placeholder('Admin')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->timezone('Asia/Jakarta')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('is_pinned', 'desc')
            ->filters([
                TernaryFilter::make('is_pinned')
                    ->label('Filter Tersemat'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
