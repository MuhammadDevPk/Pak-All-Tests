<?php

namespace App\Filament\Resources\Domains\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Domain Information')
                    ->description('Define the exam domain details and its position in the hierarchy.')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Parent Domain')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('Select Parent (Leave empty for Top-Level Domain)'),

                        TextInput::make('name')
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
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        TextInput::make('code')
                            ->label('Developer Code')
                            ->placeholder('e.g. PMA_INITIAL, CSS_COMPULSORY')
                            ->maxLength(100),

                        TextInput::make('path')
                            ->label('LTree Path (Auto-generated)')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Calculated automatically on save'),

                        Toggle::make('is_active')
                            ->label('Active Domain')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
