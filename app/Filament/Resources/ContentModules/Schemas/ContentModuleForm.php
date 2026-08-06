<?php

namespace App\Filament\Resources\ContentModules\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ContentModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('domain_id')
                    ->relationship('domain', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('module_type')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('content_meta'),
            ]);
    }
}
