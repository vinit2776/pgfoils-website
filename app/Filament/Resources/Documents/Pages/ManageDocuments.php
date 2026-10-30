<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Pages\AddDocument;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ManageRecords;

class ManageDocuments extends ManageRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        // Adding goes through the three-step wizard: Upload, Details, Publish.
        return [Action::make('add')->label('Add document')->icon('heroicon-o-plus')->url(AddDocument::getUrl())];
    }
}
