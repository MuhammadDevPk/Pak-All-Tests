<?php

namespace App\Filament\Resources\UserSubscriptions\Tables;

use App\Models\Domain;
use App\Models\UserSubscription;
use App\Services\SubscriptionManager;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class UserSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query(
                UserSubscription::query()->with([
                    'user' => fn ($query) => $query->withCount('referralsGiven'),
                    'domain',
                ])
            )
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Subscriber')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->user?->email),

                TextColumn::make('user.is_guest')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Guest' : 'Registered')
                    ->color(fn ($state) => $state ? 'warning' : 'success')
                    ->sortable(),

                TextColumn::make('domain.name')
                    ->label('Exam Domain')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'easypaisa' => 'EasyPaisa',
                        'jazzcash' => 'JazzCash',
                        'bank_transfer' => 'IBFT Bank',
                        'free_trial' => 'Free Trial',
                        default => $state ?? '—',
                    })
                    ->placeholder('—'),

                TextColumn::make('user.referrals_given_count')
                    ->label('Referrals')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn ($state) => $state > 0 ? "{$state} referred" : '0')
                    ->sortable(),

                TextColumn::make('transaction_id')
                    ->label('TRX ID')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('TRX ID copied to clipboard')
                    ->placeholder('—')
                    ->fontFamily('mono'),

                ImageColumn::make('receipt_url')
                    ->label('Receipt')
                    ->disk('public')
                    ->circular(false)
                    ->imageHeight(40)
                    ->url(fn ($record) => $record->receipt_url ? asset('storage/'.$record->receipt_url) : null, true)
                    ->placeholder('No file'),

                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->placeholder('Lifetime / None'),

                TextColumn::make('user.created_at')
                    ->label('Account Age')
                    ->since()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_guest')
                    ->label('Candidate Type')
                    ->options([
                        'registered' => 'Registered Candidates',
                        'guest' => 'Guest Passes',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (($data['value'] ?? null) === 'registered') {
                            $query->whereHas('user', fn ($q) => $q->where('is_guest', false));
                        } elseif (($data['value'] ?? null) === 'guest') {
                            $query->whereHas('user', fn ($q) => $q->where('is_guest', true));
                        }
                    }),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Verification',
                        'active' => 'Active',
                        'rejected' => 'Rejected',
                        'expired' => 'Expired',
                    ]),

                SelectFilter::make('payment_method')
                    ->label('Payment Gateway')
                    ->options([
                        'easypaisa' => 'EasyPaisa',
                        'jazzcash' => 'JazzCash',
                        'bank_transfer' => 'IBFT Bank',
                    ]),

                SelectFilter::make('domain_id')
                    ->label('Exam Domain')
                    ->options(fn (): array => Cache::remember(
                        'domains_select_options',
                        3600,
                        fn () => Domain::query()->orderBy('name')->pluck('name', 'id')->all(),
                    )),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn ($record): bool => in_array($record->status, ['pending', 'rejected', 'expired']))
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => "Approve {$record->domain?->name} Access")
                    ->modalDescription(fn ($record) => "Are you sure you want to approve {$record->user?->name}'s subscription? Access will be granted for 1 year.")
                    ->action(function (UserSubscription $record): void {
                        app(SubscriptionManager::class)->activate($record);

                        Notification::make()
                            ->title('Subscription Approved')
                            ->body("Access unlocked for {$record->user?->name}.")
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn ($record): bool => $record->status === 'pending')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason (Displayed to Candidate)')
                            ->placeholder('e.g. Transaction ID was not found in bank SMS or screenshot was blurry.')
                            ->required(),
                    ])
                    ->modalHeading('Reject Subscription Proof')
                    ->modalSubmitActionLabel('Confirm Rejection')
                    ->action(function (UserSubscription $record, array $data): void {
                        app(SubscriptionManager::class)->reject($record, $data['rejection_reason']);

                        Notification::make()
                            ->title('Subscription Rejected')
                            ->body("Rejection reason recorded for {$record->user?->name}.")
                            ->warning()
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
