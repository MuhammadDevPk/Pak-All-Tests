<?php

namespace App\Filament\Resources\DomainFlows\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class DomainFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Flow Identity & Versioning')
                    ->description('Bind this server-driven UI configuration to an exam domain.')
                    ->icon(Heroicon::OutlinedAdjustmentsVertical)
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('domain_id')
                                ->label('Target Domain')
                                ->options(fn (): array => Cache::remember(
                                    'domains_select_options',
                                    3600,
                                    fn () => \App\Models\Domain::query()
                                        ->orderBy('name')
                                        ->limit(200)
                                        ->pluck('name', 'id')
                                        ->all(),
                                ))
                                ->searchable()
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->placeholder('Select Exam Domain'),

                            TextInput::make('version')
                                ->label('Schema Version')
                                ->numeric()
                                ->default(1)
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Active Schema')
                                ->helperText('Toggle to activate this SDUI configuration.')
                                ->default(true)
                                ->required(),
                        ]),
                    ]),

                Section::make('Server-Driven UI (SDUI) Layout Schema')
                    ->description('Define tab navigation, layout structures, and Vue 3 component mappings.')
                    ->icon(Heroicon::OutlinedWindow)
                    ->schema([
                        KeyValue::make('ui_schema')
                            ->label('UI Component Mapping (JSON Pairs)')
                            ->keyLabel('Layout / Tab Key')
                            ->valueLabel('Vue Component / Config')
                            ->keyPlaceholder('e.g. layout')
                            ->valuePlaceholder('e.g. two_tiered_tabs')
                            ->addActionLabel('Add Component Mapping')
                            ->reorderable()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Pedagogical & Gating Rules Schema')
                    ->description('Define passing percentages, time limits, and prerequisite gating criteria.')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->schema([
                        KeyValue::make('learning_schema')
                            ->label('Learning & Test Rules (JSON Pairs)')
                            ->keyLabel('Rule Identifier')
                            ->valueLabel('Rule Threshold / Limit')
                            ->keyPlaceholder('e.g. time_limit_minutes')
                            ->valuePlaceholder('e.g. 45')
                            ->addActionLabel('Add Rule Definition')
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
