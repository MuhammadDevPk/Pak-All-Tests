<?php

namespace App\Filament\Resources\DomainFlows\Tables;

use App\Models\DomainFlow;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class DomainFlowsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->records(fn (
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ): Collection => DomainFlow::getCachedTableRecords(
                fn () => DomainFlow::query()->with('domain')->orderBy('id', 'asc')->get(),
                ['domain'],
                $search,
                ['domain.name', 'version'],
                $sortColumn,
                $sortDirection ?? 'asc',
            ))
            ->columns([
                TextColumn::make('domain.name')
                    ->label('Domain')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('version')
                    ->label('Schema Version')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
