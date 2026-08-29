<?php

namespace App\Filament\Resources\UserSubscriptions\Schemas;

use App\Models\Domain;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class UserSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscriber & Target Exam Domain')
                    ->description('Candidate identity and enrolled examination portal.')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('user_id')
                                ->label('Subscriber User')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->required()
                                ->disabled(fn (string $operation): bool => $operation === 'edit')
                                ->placeholder('Search user by name or email'),

                            Select::make('domain_id')
                                ->label('Target Exam Domain')
                                ->options(fn (): array => Cache::remember(
                                    'domains_select_options',
                                    3600,
                                    fn () => Domain::query()
                                        ->orderBy('name')
                                        ->limit(200)
                                        ->pluck('name', 'id')
                                        ->all(),
                                ))
                                ->searchable()
                                ->required()
                                ->disabled(fn (string $operation): bool => $operation === 'edit')
                                ->placeholder('Select Exam Domain'),
                        ]),
                    ]),

                Section::make('Payment Proof & Receipt Verification')
                    ->description('Inspect candidate payment gateway transaction and uploaded receipt proof.')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('payment_method')
                                ->label('Payment Gateway')
                                ->options([
                                    'easypaisa' => 'EasyPaisa Mobile Wallet',
                                    'jazzcash' => 'JazzCash Mobile Wallet',
                                    'bank_transfer' => 'Direct Bank Transfer (IBFT)',
                                ])
                                ->disabled(fn (string $operation): bool => $operation === 'edit')
                                ->native(false),

                            TextInput::make('transaction_id')
                                ->label('Bank / SMS Transaction ID (TRX ID)')
                                ->placeholder('e.g. TRX-9988776655')
                                ->maxLength(100)
                                ->disabled(fn (string $operation): bool => $operation === 'edit'),

                            TextEntry::make('receipt_preview')
                                ->label('Candidate Uploaded Receipt Proof')
                                ->state(function ($record): HtmlString {
                                    if (! $record || ! $record->receipt_url) {
                                        return new HtmlString('<div class="text-sm text-gray-500 italic p-3 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">No payment receipt screenshot uploaded by candidate.</div>');
                                    }

                                    $url = asset('storage/'.$record->receipt_url);
                                    $isPdf = str_ends_with(strtolower($record->receipt_url), '.pdf');

                                    if ($isPdf) {
                                        return new HtmlString('
                                            <div class="flex items-center gap-3 p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                                <span class="text-2xl">📄</span>
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-semibold">PDF Payment Receipt / Bank Statement</span>
                                                    <a href="'.$url.'" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:underline">
                                                        Open PDF in New Window ↗
                                                    </a>
                                                </div>
                                            </div>
                                        ');
                                    }

                                    return new HtmlString('
                                        <div class="flex flex-col gap-2">
                                            <a href="'.$url.'" target="_blank" rel="noopener noreferrer" class="inline-block group">
                                                <div class="relative overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 max-w-lg bg-gray-50 dark:bg-gray-800">
                                                    <img src="'.$url.'" alt="Receipt Screenshot" class="w-full h-auto max-h-96 object-contain hover:scale-[1.01] transition-transform duration-200" />
                                                </div>
                                            </a>
                                            <a href="'.$url.'" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:underline inline-flex items-center gap-1">
                                                <span>View Full High-Resolution Image ↗</span>
                                            </a>
                                        </div>
                                    ');
                                })
                                ->html()
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Verification Decision & Access Timeline')
                    ->description('Configure subscription status, validity period, or document rejection rationale.')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Subscription Status')
                                ->options([
                                    'pending' => 'Pending Verification',
                                    'active' => 'Active (Access Granted)',
                                    'expired' => 'Expired',
                                    'rejected' => 'Rejected',
                                ])
                                ->default('pending')
                                ->required()
                                ->native(false),

                            Textarea::make('rejection_reason')
                                ->label('Rejection Reason (Shown to Candidate)')
                                ->placeholder('Explain why the payment proof was rejected (e.g. TRX ID not found in bank SMS)...')
                                ->rows(2)
                                ->visible(fn ($get): bool => $get('status') === 'rejected')
                                ->required(fn ($get): bool => $get('status') === 'rejected'),

                            DateTimePicker::make('starts_at')
                                ->label('Subscription Start Date & Time')
                                ->native(false),

                            DateTimePicker::make('expires_at')
                                ->label('Subscription Expiry Date & Time')
                                ->native(false),
                        ]),
                    ]),
            ]);
    }
}
