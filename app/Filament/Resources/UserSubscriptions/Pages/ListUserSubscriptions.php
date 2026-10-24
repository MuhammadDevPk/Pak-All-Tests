<?php

namespace App\Filament\Resources\UserSubscriptions\Pages;

use App\Filament\Resources\UserSubscriptions\UserSubscriptionResource;
use App\Models\UserSubscription;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUserSubscriptions extends ListRecords
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $pendingCount = UserSubscription::where('status', 'pending')->count();
        $registeredCount = UserSubscription::whereHas('user', fn ($q) => $q->where('is_guest', false))->count();
        $guestCount = UserSubscription::whereHas('user', fn ($q) => $q->where('is_guest', true))->count();

        return [
            'all' => Tab::make('All Subscriptions'),

            'registered' => Tab::make('Registered Candidates')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('is_guest', false)))
                ->badge($registeredCount > 0 ? (string) $registeredCount : null)
                ->badgeColor('success'),

            'guests' => Tab::make('Guest Sign-ins')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('user', fn ($q) => $q->where('is_guest', true)))
                ->badge($guestCount > 0 ? (string) $guestCount : null)
                ->badgeColor('gray'),

            'pending' => Tab::make('Pending Verification')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge($pendingCount > 0 ? (string) $pendingCount : null)
                ->badgeColor('warning'),

            'active' => Tab::make('Active Access')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'active')),

            'rejected' => Tab::make('Rejected Proofs')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
        ];
    }
}
