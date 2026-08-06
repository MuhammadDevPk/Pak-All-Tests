<?php

namespace App\Filament\Resources\UserSubscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserSubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('domain_id')
                    ->relationship('domain', 'name')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('receipt_url')
                    ->url(),
                TextInput::make('payment_method'),
                TextInput::make('transaction_id'),
                Textarea::make('rejection_reason')
                    ->columnSpanFull(),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('expires_at'),
            ]);
    }
}
