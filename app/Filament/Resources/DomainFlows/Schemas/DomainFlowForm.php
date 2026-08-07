<?php

namespace App\Filament\Resources\DomainFlows\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DomainFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('SDUI Flow Configuration')
                    ->description('Define the server-driven UI layout and pedagogical rules for this domain.')
                    ->schema([
                        Select::make('domain_id')
                            ->label('Domain')
                            ->relationship('domain', 'name')
                            ->searchable()
                            ->preload()
                            ->unique(ignoreRecord: true)
                            ->required(),

                        TextInput::make('version')
                            ->numeric()
                            ->default(1)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active Flow Schema')
                            ->default(true)
                            ->required(),

                        KeyValue::make('ui_schema')
                            ->label('UI Component Schema (JSON)')
                            ->keyLabel('Property / Layout')
                            ->valueLabel('Value / Definition')
                            ->helperText('Defines tab layouts, Vue 3 component structures, and subject grids.')
                            ->required()
                            ->columnSpanFull(),

                        KeyValue::make('learning_schema')
                            ->label('Learning Rules & Gating Schema (JSON)')
                            ->keyLabel('Rule / Parameter')
                            ->valueLabel('Value')
                            ->helperText('Defines test time limits, passing score percentage, and gating criteria.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
