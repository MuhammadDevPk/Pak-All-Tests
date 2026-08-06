<?php

namespace App\Filament\Resources\DomainFlows;

use App\Filament\Resources\DomainFlows\Pages\CreateDomainFlow;
use App\Filament\Resources\DomainFlows\Pages\EditDomainFlow;
use App\Filament\Resources\DomainFlows\Pages\ListDomainFlows;
use App\Filament\Resources\DomainFlows\Schemas\DomainFlowForm;
use App\Filament\Resources\DomainFlows\Tables\DomainFlowsTable;
use App\Models\DomainFlow;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DomainFlowResource extends Resource
{
    protected static ?string $model = DomainFlow::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DomainFlowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DomainFlowsTable::configure($table);
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
            'index' => ListDomainFlows::route('/'),
            'create' => CreateDomainFlow::route('/create'),
            'edit' => EditDomainFlow::route('/{record}/edit'),
        ];
    }
}
