<?php

namespace App\Filament\Resources\Domains\Tables;

use App\Models\Domain;
use App\Services\Ai\KnowledgeVectorizationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
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
                fn () => Domain::query()->with('parent')->withCount('knowledgeEmbeddings')->orderBy('id', 'asc')->get(),
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

                TextColumn::make('knowledge_embeddings_count')
                    ->label('AI Vectors')
                    ->badge()
                    ->color(fn (?int $state): string => ($state ?? 0) > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => ($state ?? 0) > 0 ? "{$state} Vectors" : 'Unsynced')
                    ->icon(Heroicon::OutlinedCpuChip)
                    ->sortable(),

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
                Action::make('syncKnowledge')
                    ->label('Sync AI Vectors')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Domain $record) => "Vectorize Knowledge Base: {$record->name}")
                    ->modalDescription('This extracts and vectorizes all content modules, syllabus documents, and question bank items attached to this domain using pgvector.')
                    ->action(function (Domain $record, KnowledgeVectorizationService $service) {
                        $result = $service->vectorizeDomain($record);
                        Domain::flushCache();

                        Notification::make()
                            ->title('AI Knowledge Vectorized')
                            ->body("Vectorized {$result['modules_count']} modules and {$result['questions_count']} questions ({$result['embeddings_count']} embeddings generated).")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
