<?php

namespace App\Filament\Resources\Domains\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DomainsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            // Senior Architect Mandate: Eager load relationships to guarantee sub-millisecond execution & zero N+1 queries
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent'))
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
