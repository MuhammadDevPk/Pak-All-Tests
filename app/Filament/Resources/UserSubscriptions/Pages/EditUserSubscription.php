<?php

namespace App\Filament\Resources\UserSubscriptions\Pages;

use App\Filament\Resources\UserSubscriptions\UserSubscriptionResource;
use App\Models\UserSubscription;
use App\Services\SubscriptionManager;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

/**
 * @property UserSubscription $record
 *
 * @method UserSubscription getRecord()
 */
class EditUserSubscription extends EditRecord
{
    protected static string $resource = UserSubscriptionResource::class;

    public function getRecord(): UserSubscription
    {
        /** @var UserSubscription $record */
        $record = parent::getRecord();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('Approve Subscription')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn (): bool => in_array($this->getRecord()->status, ['pending', 'rejected', 'expired']))
                ->requiresConfirmation()
                ->modalHeading('Approve Subscription & Grant Access')
                ->modalDescription(fn (): string => "Activate {$this->getRecord()->user?->name}'s subscription for 1 full year?")
                ->action(function (): void {
                    $subscription = $this->getRecord();

                    app(SubscriptionManager::class)->activate($subscription);

                    Notification::make()
                        ->title('Subscription Approved')
                        ->body("Access activated for {$subscription->user?->name}.")
                        ->success()
                        ->send();

                    $this->fillForm();
                }),

            Action::make('reject')
                ->label('Reject Proof')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn (): bool => $this->getRecord()->status === 'pending')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Rejection Reason (Displayed to Candidate)')
                        ->placeholder('e.g. Transaction ID was not found in bank SMS or screenshot was blurry.')
                        ->required(),
                ])
                ->modalHeading('Reject Payment Proof')
                ->modalSubmitActionLabel('Confirm Rejection')
                ->action(function (array $data): void {
                    $subscription = $this->getRecord();

                    app(SubscriptionManager::class)->reject($subscription, $data['rejection_reason']);

                    Notification::make()
                        ->title('Subscription Rejected')
                        ->body('Rejection notice recorded.')
                        ->warning()
                        ->send();

                    $this->fillForm();
                }),

            DeleteAction::make(),
        ];
    }
}
