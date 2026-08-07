<?php

namespace App\Filament\Resources\ContentModules\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ContentModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content Module Details')
                    ->description('Categorize and configure content preparation or mock test modules.')
                    ->schema([
                        Select::make('domain_id')
                            ->label('Domain')
                            ->relationship('domain', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255),

                        Select::make('module_type')
                            ->label('Module Type')
                            ->options([
                                'preparation' => 'Preparation Module',
                                'mock_test' => 'Mock Test',
                                'pdf_repo' => 'PDF Repository',
                            ])
                            ->required(),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active Module')
                            ->default(true)
                            ->required(),

                        KeyValue::make('content_meta')
                            ->label('Content Metadata (JSON Configuration)')
                            ->keyLabel('Setting')
                            ->valueLabel('Value')
                            ->helperText('Configure duration_minutes, passing_score, instructions, or PDF links.')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
