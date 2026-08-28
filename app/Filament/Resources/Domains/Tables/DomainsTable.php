<?php

namespace App\Filament\Resources\Domains\Tables;

use App\Models\Domain;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->records(fn (
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ): Collection => Domain::getCachedTableRecords(
                fn () => Domain::query()->with('parent')->orderBy('id', 'asc')->get(),
                ['parent'],
                $search,
                ['name', 'code', 'path', 'parent.name'],
                $sortColumn,
                $sortDirection ?? 'asc',
            ))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('parent.name')
                    ->label('Parent Domain')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Top Level')
                    ->color('gray'),

                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('path')
                    ->label('LTree Path')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

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
