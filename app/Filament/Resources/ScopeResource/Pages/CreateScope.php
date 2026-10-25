<?php

namespace App\Filament\Resources\ScopeResource\Pages;

use App\Filament\Resources\ScopeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScope extends CreateRecord
{
    protected static string $resource = ScopeResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
