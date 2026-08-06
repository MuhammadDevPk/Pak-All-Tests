<?php

namespace App\Filament\Resources\DomainFlows\Pages;

use App\Filament\Resources\DomainFlows\DomainFlowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDomainFlow extends EditRecord
{
    protected static string $resource = DomainFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
