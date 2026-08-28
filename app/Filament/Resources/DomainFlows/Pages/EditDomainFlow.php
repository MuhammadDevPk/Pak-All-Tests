<?php

namespace App\Filament\Resources\DomainFlows\Pages;

use App\Filament\Resources\DomainFlows\DomainFlowResource;
use App\Filament\Resources\DomainFlows\Support\UiSchemaCompiler;
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

    /**
     * Before the form is populated, expand the stored JSON into
     * the visual builder's Repeater state.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Expand ui_schema JSON → phases Repeater state
        if (!empty($data['ui_schema']) && is_array($data['ui_schema'])) {
            $decompiled = UiSchemaCompiler::decompile($data['ui_schema']);
            $data['phases'] = $decompiled['phases'] ?? [];
        }

        // Expand learning_schema JSON → individual form fields
        if (!empty($data['learning_schema']) && is_array($data['learning_schema'])) {
            $data['passing_percentage'] = $data['learning_schema']['passing_percentage'] ?? null;
            $data['time_limit_minutes'] = $data['learning_schema']['time_limit_minutes'] ?? null;
        }

        return $data;
    }

    /**
     * Before saving, compile the visual builder state back into
     * canonical JSON to persist in the database.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Compile phases Repeater → ui_schema JSON
        if (isset($data['phases'])) {
            $data['ui_schema'] = UiSchemaCompiler::compile(['phases' => $data['phases']]);
            unset($data['phases']);
        }

        // Compile individual fields → learning_schema JSON
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

