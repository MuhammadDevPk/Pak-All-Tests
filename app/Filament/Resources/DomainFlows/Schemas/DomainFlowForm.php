<?php

namespace App\Filament\Resources\DomainFlows\Schemas;

use App\Models\Domain;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                                    fn () => Domain::query()
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
                        Textarea::make('ui_schema')
                            ->label('UI Layout Schema (JSON)')
                            ->helperText('Input a valid JSON object defining the page layout schema. Need component templates or key verification? Check the SDUI Schema Guide in the sidebar or visit /admin/sdui-schema-guide.')
                            ->rows(15)
                            ->required()
                            ->columnSpanFull()
                            ->afterStateHydrated(fn ($state, $set) => is_array($state) ? $set('ui_schema', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) : null)
                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                            ->rules([
                                fn () => function (string $attribute, $value, $fail) {
                                    $array = is_string($value) ? json_decode($value, true) : $value;
                                    if (json_last_error() !== JSON_ERROR_NONE) {
                                        $fail('The '.$attribute.' must be a valid JSON string: '.json_last_error_msg());
                                        return;
                                    }

                                    if (is_array($array)) {
                                        $validator = new \App\Services\SduiSchemaValidator();
                                        $result = $validator->validate($array);
                                        if (!$result['valid']) {
                                            $fail('SDUI Schema Error: '.implode(' | ', $result['errors']));
                                        }
                                    }
                                },
                            ]),
                    ]),

                Section::make('Pedagogical & Gating Rules Schema')
                    ->description('Define passing percentages, time limits, and prerequisite gating criteria.')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->schema([
                        Textarea::make('learning_schema')
                            ->label('Learning & Test Rules (JSON)')
                            ->helperText('Input a valid JSON object defining passing, time limits, or grading configurations.')
                            ->rows(6)
                            ->columnSpanFull()
                            ->afterStateHydrated(fn ($state, $set) => is_array($state) ? $set('learning_schema', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) : null)
                            ->dehydrateStateUsing(fn ($state) => json_decode($state, true))
                            ->rules([
                                fn () => function (string $attribute, $value, $fail) {
                                    if (is_string($value)) {
                                        json_decode($value);
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            $fail('The '.$attribute.' must be a valid JSON string: '.json_last_error_msg());
                                        }
                                    }
                                },
                            ]),
                    ]),
            ]);
    }
}
