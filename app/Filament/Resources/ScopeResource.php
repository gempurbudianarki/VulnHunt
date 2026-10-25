<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScopeResource\Pages;
use App\Models\Scope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontFamily;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ScopeResource extends Resource
{
    protected static ?string $model = Scope::class;

    protected static ?string $navigationIcon = 'heroicon-o-viewfinder-circle';

    protected static ?string $navigationGroup = 'Ruang Lingkup & Aturan';

    protected static ?string $navigationLabel = 'Target Scope';

    protected static ?string $modelLabel = 'Target Scope';

    protected static ?string $pluralModelLabel = 'Target Scope Pengujian';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Target / Sistem')
                    ->required()
                    ->maxLength(255),

                TextInput::make('target')
                    ->label('Domain / URL Target')
                    ->required()
                    ->maxLength(255),

                Select::make('asset_type')
                    ->label('Tipe Aset')
                    ->options([
                        'web' => 'Aplikasi Web',
                        'api' => 'REST API / Backend',
                        'mobile' => 'Aplikasi Mobile',
                        'network' => 'Infrastruktur Jaringan',
                        'other' => 'Lainnya',
                    ])
                    ->required()
                    ->default('web'),

                Select::make('scope_status')
                    ->label('Status Scope')
                    ->options([
                        'in_scope' => 'In Scope (Diizinkan Diuji)',
                        'out_of_scope' => 'Out of Scope (Dilarang Diuji)',
                    ])
                    ->required()
                    ->default('in_scope'),

                TextInput::make('criticality_weight')
                    ->label('Bobot Kritis (Criticality Weight)')
                    ->helperText('Rentang 1.00 sampai 2.50. Mengalikan poin akhir laporan pada target ini.')
                    ->numeric()
                    ->default(1.00)
                    ->step(0.05)
                    ->minValue(1.00)
                    ->maxValue(2.50)
                    ->required(),

                Toggle::make('is_active')
                    ->label('Target Aktif')
                    ->default(true)
                    ->required(),

                Textarea::make('description')
                    ->label('Deskripsi Target')
                    ->rows(3)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Catatan Khusus / Batasan Pengujian')
                    ->helperText('Misal: aturan jam pengujian, larangan brute-force, larangan manipulasi akun admin.')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Target')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('target')
                    ->label('Domain / URL')
                    ->searchable()
                    ->fontFamily(FontFamily::Mono),

                TextColumn::make('scope_status')
                    ->label('Scope')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_scope' => 'In-Scope',
                        'out_of_scope' => 'Out-of-Scope',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in_scope' => 'success',
                        'out_of_scope' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('criticality_weight')
                    ->label('Bobot')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2).'x')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('reports_count')
                    ->counts('reports')
                    ->label('Total Laporan')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('scope_status')
                    ->options([
                        'in_scope' => 'In Scope',
                        'out_of_scope' => 'Out of Scope',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListScopes::route('/'),
            'create' => Pages\CreateScope::route('/create'),
            'edit' => Pages\EditScope::route('/{record}/edit'),
        ];
    }
}
