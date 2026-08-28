<?php

namespace App\Filament\Resources\ContentModules\Tables;

use App\Models\ContentModule;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContentModulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->records(fn (
                ?string $search,
                ?string $sortColumn,
                ?string $sortDirection,
            ): \Illuminate\Support\Collection => ContentModule::getCachedTableRecords(
                fn () => ContentModule::query()->with('domain')->orderBy('domain_id', 'asc')->orderBy('sort_order', 'asc')->orderBy('id', 'asc')->get(),
                ['domain'],
                $search,
                ['title', 'slug', 'module_type', 'domain.name'],
                $sortColumn,
                $sortDirection ?? 'asc',
            ))
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('domain.name')
                    ->label('Domain')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('module_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'preparation' => 'info',
                        'mock_test' => 'warning',
                        'pdf_repo' => 'success',
                        default => 'gray',
                    })
                    ->searchable(),

                TextColumn::make('sort_order')
                    ->numeric()
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
