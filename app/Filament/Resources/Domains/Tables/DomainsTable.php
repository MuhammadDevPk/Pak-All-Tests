<?php

namespace App\Filament\Resources\Domains\Tables;

use App\Models\Domain;
use App\Models\PlatformSetting;
use App\Services\Ai\KnowledgeVectorizationService;
use BackedEnum;
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
                fn () => Domain::query()->with('parent')->withCount(['knowledgeEmbeddings', 'chatMessages'])->orderBy('id', 'asc')->get(),
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

                TextColumn::make('chat_messages_count')
                    ->label('AI Chats')
                    ->badge()
                    ->color(fn (?int $state): string => ($state ?? 0) > 0 ? 'info' : 'gray')
                    ->formatStateUsing(fn (?int $state): string => ($state ?? 0) > 0 ? "{$state} Messages" : 'No Chats')
                    ->icon(Heroicon::OutlinedChatBubbleLeftRight)
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('requires_subscription')
                    ->label('Payment Required')
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
            ->headerActions([
                Action::make('toggleGlobalFree')
                    ->label(fn (): string => PlatformSetting::isAllDomainsFree() ? 'Platform Mode: ALL FREE (Bypassed)' : 'Platform Mode: Payment Gated')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color(fn (): string => PlatformSetting::isAllDomainsFree() ? 'success' : 'warning')
                    ->requiresConfirmation()
                    ->modalHeading('Toggle Global Free Mode')
                    ->modalDescription(fn (): string => PlatformSetting::isAllDomainsFree()
                        ? 'All domains are currently 100% free. Clicking confirm will re-enable per-domain payment requirements.'
                        : 'Clicking confirm will make ALL domains free across the platform, immediately bypassing payment uploads.')
                    ->action(function () {
                        $current = PlatformSetting::isAllDomainsFree();
                        PlatformSetting::set('all_domains_free', ! $current);
                        Domain::flushCache();

                        Notification::make()
                            ->title(! $current ? 'All Domains Set To FREE' : 'Payment Requirements Restored')
                            ->body(! $current ? 'Students can now access all test content without payment screenshots.' : 'Per-domain subscription rules are now active.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('togglePayment')
                    ->label(fn (Domain $record): string => $record->requires_subscription ? 'Make Free' : 'Require Payment')
                    ->icon(fn (Domain $record): BackedEnum => $record->requires_subscription ? Heroicon::OutlinedGift : Heroicon::OutlinedCurrencyDollar)
                    ->color(fn (Domain $record): string => $record->requires_subscription ? 'success' : 'warning')
                    ->action(function (Domain $record) {
                        $record->update(['requires_subscription' => ! $record->requires_subscription]);
                        Domain::flushCache();

                        Notification::make()
                            ->title("Payment status updated for {$record->name}")
                            ->body($record->requires_subscription ? 'Paid subscription is now required.' : 'Domain is now free for all students.')
                            ->success()
                            ->send();
                    }),

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
