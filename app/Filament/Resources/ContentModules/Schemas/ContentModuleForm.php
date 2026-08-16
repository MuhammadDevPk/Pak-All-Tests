<?php

namespace App\Filament\Resources\ContentModules\Schemas;

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
use Illuminate\Support\Str;

class ContentModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module Core Settings')
                    ->description('Categorize and define basic module properties and domain linking.')
                    ->icon(Heroicon::OutlinedFolder)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('domain_id')
                                ->label('Domain')
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
                                ->required()
                                ->placeholder('Select Domain'),

                            Select::make('module_type')
                                ->label('Module Type')
                                ->options([
                                    'preparation' => 'Preparation Module',
                                    'mock_test' => 'Mock Test',
                                    'pdf_repo' => 'PDF Repository',
                                ])
                                ->required()
                                ->native(false),

                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g. Verbal Intelligence Test 1')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug($state));
                                    }
                                }),

                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g. verbal-intelligence-test-1'),

                            TextInput::make('sort_order')
                                ->label('Sort Order Index')
                                ->numeric()
                                ->default(0)
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Module Active Status')
                                ->helperText('Control whether this module is visible to students.')
                                ->default(true)
                                ->required(),
                        ]),
                    ]),

                Section::make('Content Metadata & Configuration')
                    ->description('Specify test parameters like duration, passing score, or PDF resource links.')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->schema([
                        Textarea::make('content_meta')
                            ->label('Module Metadata (JSON)')
                            ->helperText('Input a valid JSON object defining duration, passing limits, or other custom variables.')
                            ->placeholder("{\n  \"duration_minutes\": 20,\n  \"passing_score\": 60,\n  \"description\": \"Test details\"\n}")
                            ->rows(6)
                            ->required()
                            ->columnSpanFull()
                            ->afterStateHydrated(fn ($state, $set) => is_array($state) ? $set('content_meta', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) : null)
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
