<?php

namespace App\Filament\Resources\ContentModules\Pages;

use App\Filament\Resources\ContentModules\ContentModuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContentModules extends ListRecords
{
    protected static string $resource = ContentModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
