<?php

namespace App\Filament\Resources\DomainFlows\Pages;

use App\Filament\Resources\DomainFlows\DomainFlowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDomainFlows extends ListRecords
{
    protected static string $resource = DomainFlowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
