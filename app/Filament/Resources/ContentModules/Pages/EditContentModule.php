<?php

namespace App\Filament\Resources\ContentModules\Pages;

use App\Filament\Resources\ContentModules\ContentModuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContentModule extends EditRecord
{
    protected static string $resource = ContentModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
