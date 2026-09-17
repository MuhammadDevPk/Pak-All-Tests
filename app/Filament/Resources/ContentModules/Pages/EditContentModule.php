<?php

namespace App\Filament\Resources\ContentModules\Pages;

use App\Filament\Resources\ContentModules\Concerns\HandlesModuleFiles;
use App\Filament\Resources\ContentModules\ContentModuleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContentModule extends EditRecord
{
    use HandlesModuleFiles;

    protected static string $resource = ContentModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->processFilesBeforeSave($data);
    }
}
