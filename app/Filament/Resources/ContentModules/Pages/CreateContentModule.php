<?php

namespace App\Filament\Resources\ContentModules\Pages;

use App\Filament\Resources\ContentModules\Concerns\HandlesModuleFiles;
use App\Filament\Resources\ContentModules\ContentModuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContentModule extends CreateRecord
{
    use HandlesModuleFiles;

    protected static string $resource = ContentModuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->processFilesBeforeSave($data);
    }
}
