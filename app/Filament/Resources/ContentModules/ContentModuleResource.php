<?php

namespace App\Filament\Resources\ContentModules;

use App\Filament\Resources\ContentModules\Pages\CreateContentModule;
use App\Filament\Resources\ContentModules\Pages\EditContentModule;
use App\Filament\Resources\ContentModules\Pages\ListContentModules;
use App\Filament\Resources\ContentModules\Schemas\ContentModuleForm;
use App\Filament\Resources\ContentModules\Tables\ContentModulesTable;
use App\Models\ContentModule;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContentModuleResource extends Resource
{
    protected static ?string $model = ContentModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return ContentModuleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentModulesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentModules::route('/'),
            'create' => CreateContentModule::route('/create'),
            'edit' => EditContentModule::route('/{record}/edit'),
        ];
    }
}
