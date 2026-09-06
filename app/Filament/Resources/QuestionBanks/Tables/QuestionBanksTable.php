<?php

namespace App\Filament\Resources\QuestionBanks\Tables;

use App\Models\QuestionBank;
use App\Services\Ai\KnowledgeVectorizationService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;

class QuestionBanksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query(QuestionBank::query())
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('question_type')
                    ->label('Type')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('question_text')
                    ->label('Question Prompt')
                    ->html()
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('difficulty_level')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
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
                Action::make('vectorize')
                    ->label('Vectorize')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('success')
                    ->action(function (QuestionBank $record, KnowledgeVectorizationService $service) {
                        $created = $service->vectorizeQuestion($record);
                        if (empty($created)) {
                            Notification::make()
                                ->title('No Associated Domains')
                                ->body('Attach this question to a content module under a domain before vectorizing.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Question Vectorized')
                            ->body('Question vectors successfully generated and stored.')
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
