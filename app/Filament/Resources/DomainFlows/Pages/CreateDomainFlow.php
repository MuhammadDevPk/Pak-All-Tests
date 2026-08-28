<?php

namespace App\Filament\Resources\DomainFlows\Pages;

use App\Filament\Resources\DomainFlows\DomainFlowResource;
use App\Filament\Resources\DomainFlows\Support\UiSchemaCompiler;
use Filament\Resources\Pages\CreateRecord;

class CreateDomainFlow extends CreateRecord
{
    protected static string $resource = DomainFlowResource::class;

    /**
     * Compile the visual builder state into canonical JSON
     * before the record is inserted into the database.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Compile phases Repeater → ui_schema JSON
        if (isset($data['phases'])) {
            $data['ui_schema'] = UiSchemaCompiler::compile(['phases' => $data['phases']]);
            unset($data['phases']);
        }

        // Compile visual learning schema fields → learning_schema JSON
        $data['learning_schema'] = array_filter([
            'passing_percentage' => isset($data['passing_percentage'])
                ? (int) $data['passing_percentage']
                : null,
            'time_limit_minutes' => isset($data['time_limit_minutes'])
                ? (int) $data['time_limit_minutes']
                : null,
        ], fn ($v) => $v !== null);

        unset($data['passing_percentage'], $data['time_limit_minutes']);

        return $data;
    }
}
