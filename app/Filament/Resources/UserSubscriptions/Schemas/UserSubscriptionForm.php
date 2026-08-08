<?php

namespace App\Filament\Resources\UserSubscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class UserSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscriber & Domain Assignment')
                    ->description('Select the target aspirant user and their subscribed exam domain.')
                    ->icon(Heroicon::OutlinedUser)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('user_id')
                                ->label('Subscriber User')
                                ->relationship('user', 'name')
                                ->searchable()
                                ->required()
                                ->placeholder('Search user by name or email'),

                            Select::make('domain_id')
                                ->label('Target Exam Domain')
                                ->options(fn (): array => Cache::remember(
                                    'domains_select_options',
                                    3600,
                                    fn () => \App\Models\Domain::query()
                                        ->orderBy('name')
                                        ->limit(200)
                                        ->pluck('name', 'id')
                                        ->all(),
                                ))
                                ->searchable()
                                ->required()
                                ->placeholder('Select Exam Domain'),
                        ]),
                    ]),

                Section::make('Payment Proof & Verification')
                    ->description('Verify manual mobile wallet / bank transfer transactions and receipt proof.')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('status')
                                ->label('Subscription Status')
                                ->options([
                                    'pending' => 'Pending Verification',
                                    'active' => 'Active',
                                    'expired' => 'Expired',
                                    'rejected' => 'Rejected',
                                ])
                                ->default('pending')
                                ->required()
                                ->native(false),

                            Select::make('payment_method')
                                ->label('Payment Gateway Method')
                                ->options([
                                    'easypaisa' => 'EasyPaisa Mobile Wallet',
                                    'jazzcash' => 'JazzCash Mobile Wallet',
                                    'bank_transfer' => 'Direct Bank Transfer',
                                ])
                                ->native(false),

                            TextInput::make('transaction_id')
                                ->label('Bank / SMS Transaction ID (TRX)')
                                ->placeholder('e.g. TRX-9988776655')
                                ->maxLength(100)
                                ->columnSpanFull(),

                            FileUpload::make('receipt_url')
                                ->label('Uploaded Receipt Screenshot')
                                ->image()
                                ->imagePreviewHeight('280')
                                ->directory('receipts')
                                ->visibility('public')
                                ->openable()
                                ->downloadable()
                                ->columnSpanFull(),

                            Textarea::make('rejection_reason')
                                ->label('Rejection Reason (Shown to Candidate)')
                                ->placeholder('Specify why the submitted payment receipt was rejected...')
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                    ]),

                Section::make('Access Timeline')
                    ->description('Define the active subscription start and expiry timestamp.')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Grid::make(2)->schema([
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
