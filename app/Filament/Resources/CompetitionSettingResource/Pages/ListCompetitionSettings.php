<?php

namespace App\Filament\Resources\CompetitionSettingResource\Pages;

use App\Filament\Resources\CompetitionSettingResource;
use App\Services\CompetitionSettings;
use Filament\Resources\Pages\ListRecords;

class ListCompetitionSettings extends ListRecords
{
    protected static string $resource = CompetitionSettingResource::class;

    public function mount(): void
    {
        parent::mount();

        // Pastikan seluruh kunci default resmi ada di database sehingga dapat dikelola admin
        app(CompetitionSettings::class)->ensureAllKeysSeeded();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
