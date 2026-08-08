<?php

namespace App\Filament\Resources\Domains\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Domain Hierarchy & Identity')
                    ->description('Define the exam domain details and its position in the PostgreSQL ltree hierarchy.')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('parent_id')
                                ->label('Parent Domain')
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
                                ->nullable()
                                ->placeholder('Select Parent (Leave empty for Top-Level Domain)'),

                            TextInput::make('name')
                                ->label('Domain Name')
                                ->placeholder('e.g. PMA Long Course Initial')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug($state));
                                    }
                                }),

                            TextInput::make('slug')
                                ->label('Clean URL Slug')
                                ->placeholder('e.g. pma-initial-course')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),

                            TextInput::make('code')
                                ->label('Developer Reference Code')
                                ->placeholder('e.g. PMA_INITIAL, CSS_2027')
                                ->maxLength(100),

                            TextInput::make('path')
                                ->label('PostgreSQL LTree Path')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Calculated automatically on save (e.g. 1.2.5)'),

                            Toggle::make('is_active')
                                ->label('Active Status')
                                ->helperText('Controls whether this domain appears across the portal.')
                                ->default(true)
                                ->required(),
                        ]),
                    ]),
            ]);
    }
}
