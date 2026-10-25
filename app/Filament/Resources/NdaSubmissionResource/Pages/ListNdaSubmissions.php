<?php

namespace App\Filament\Resources\NdaSubmissionResource\Pages;

use App\Filament\Resources\NdaSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListNdaSubmissions extends ListRecords
{
    protected static string $resource = NdaSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
