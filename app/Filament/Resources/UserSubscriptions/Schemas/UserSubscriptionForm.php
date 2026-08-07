<?php

namespace App\Filament\Resources\UserSubscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Subscription & Subscriber Info')
                    ->description('Select subscriber user and their target exam domain.')
                    ->schema([
                        Select::make('user_id')
                            ->label('Subscriber User')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('domain_id')
                            ->label('Exam Domain')
                            ->relationship('domain', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('status')
                            ->label('Subscription Status')
                            ->options([
                                'pending' => 'Pending Verification',
                                'active' => 'Active',
                                'expired' => 'Expired',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending')
                            ->required(),

                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->options([
                                'easypaisa' => 'EasyPaisa',
                                'jazzcash' => 'JazzCash',
                                'bank_transfer' => 'Bank Transfer',
                            ]),

                        TextInput::make('transaction_id')
                            ->label('Transaction ID (TRX)')
                            ->placeholder('e.g. TRX-9988776655'),
                    ])->columns(2),

                Section::make('Payment Verification Proof')
                    ->description('View or upload payment screenshot and set access timeline.')
                    ->schema([
                        FileUpload::make('receipt_url')
                            ->label('Payment Receipt Image')
                            ->image()
                            ->directory('receipts')
                            ->visibility('public')
                            ->columnSpanFull(),

                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason (If Status = Rejected)')
                            ->placeholder('Provide reason for rejecting the submitted receipt...')
                            ->columnSpanFull(),

                        DateTimePicker::make('starts_at')
                            ->label('Subscription Start Date'),

                        DateTimePicker::make('expires_at')
                            ->label('Subscription Expiry Date'),
                    ])->columns(2),
            ]);
    }
}
