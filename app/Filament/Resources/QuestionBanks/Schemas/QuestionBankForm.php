<?php

namespace App\Filament\Resources\QuestionBanks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class QuestionBankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('question_type')
                    ->required(),
                TextInput::make('code'),
                Textarea::make('question_text')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('payload'),
                TextInput::make('difficulty_level')
                    ->required()
                    ->default('medium'),
                Textarea::make('explanation')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
