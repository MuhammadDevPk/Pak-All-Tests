<?php

declare(strict_types=1);

namespace App\Filament\Resources\Referrals\Tables;

use App\Models\Referral;
use App\Services\SubscriptionManager;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReferralsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query(Referral::query()->with(['referrer', 'referredUser', 'domain']))
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('referrer.name')
                    ->label('Referrer (Parent)')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Referral $record): string => ($record->referrer->email ?? '').($record->referrer->is_suspended ? ' ⛔ [SUSPENDED]' : '')),

                TextColumn::make('referredUser.name')
                    ->label('Referred (Child)')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Referral $record): string => ($record->referredUser->email ?? '').($record->referredUser->is_suspended ? ' ⛔ [SUSPENDED]' : '')),

                TextColumn::make('domain.name')
                    ->label('Target Domain')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('reward_days')
                    ->label('Days Awarded')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (int $state): string => "+{$state} Days")
                    ->sortable(),

                TextColumn::make('milestone_unlocked')
                    ->label('Milestone')
                    ->badge()
                    ->color('success')
                    ->placeholder('Standard')
                    ->formatStateUsing(fn (?string $state): string => $state === '30_days_milestone' ? '★ 30-Day Goal' : ($state ?? 'Standard')),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'flagged_abuse' => 'danger',
                        'rejected' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Approved',
                        'flagged_abuse' => '⚠️ Flagged Abuse',
                        'rejected' => 'Rejected',
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable(),

                TextColumn::make('abuse_reason')
                    ->label('Abuse Reason')
                    ->placeholder('None')
                    ->color('danger')
                    ->searchable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'approved' => 'Approved',
                        'flagged_abuse' => 'Flagged Abuse',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([
                Action::make('suspendReferrer')
                    ->label('Suspend Referrer')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Referrer Account')
                    ->modalDescription(fn (Referral $record): string => "Are you sure you want to permanently suspend {$record->referrer?->name} ({$record->referrer?->email})? They will immediately lose platform access.")
                    ->visible(fn (Referral $record): bool => (bool) $record->referrer && ! $record->referrer->is_suspended)
                    ->action(function (Referral $record) {
                        $record->referrer?->update([
                            'is_suspended' => true,
                            'suspension_reason' => 'Referral abuse / fraud flagged: '.($record->abuse_reason ?? 'Manual moderation'),
                        ]);
                        $record->update(['status' => 'rejected']);

                        Notification::make()
                            ->title('Referrer Account Suspended')
                            ->body("User {$record->referrer?->email} has been suspended.")
                            ->danger()
                            ->send();
                    }),

                Action::make('suspendReferred')
                    ->label('Suspend Child')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend Referred Account')
                    ->modalDescription(fn (Referral $record): string => "Are you sure you want to suspend {$record->referredUser?->name} ({$record->referredUser?->email})?")
                    ->visible(fn (Referral $record): bool => (bool) $record->referredUser && ! $record->referredUser->is_suspended)
                    ->action(function (Referral $record) {
                        $record->referredUser?->update([
                            'is_suspended' => true,
                            'suspension_reason' => 'Duplicate/fraudulent referral account',
                        ]);
                        $record->update(['status' => 'rejected']);

                        Notification::make()
                            ->title('Referred User Suspended')
                            ->danger()
                            ->send();
                    }),

                Action::make('approveFlagged')
                    ->label('Dismiss Flag & Approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Referral')
                    ->modalDescription('Dismiss the abuse flag, approve this referral, and credit +7 days access to the parent?')
                    ->visible(fn (Referral $record): bool => $record->status === 'flagged_abuse')
                    ->action(function (Referral $record) {
                        $rewardDays = 7;
                        $record->update([
                            'status' => 'approved',
                            'abuse_reason' => null,
                            'reward_days' => $rewardDays,
                        ]);

                        if ($record->referrer && $record->domain) {
                            app(SubscriptionManager::class)->extendSubscription($record->referrer, $record->domain, $rewardDays);
                        }

                        if ($record->referredUser && $record->referrer_id) {
                            $record->referredUser->update(['referred_by_id' => $record->referrer_id]);
                        }

                        Notification::make()
                            ->title('Referral Approved & Credited')
                            ->body("+{$rewardDays} days credited to {$record->referrer?->name}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
