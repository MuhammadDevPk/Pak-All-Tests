<?php

namespace App\Filament\Resources\QuestionBanks\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionBankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Question Metadata')
                    ->description('Define question type, unique code, difficulty level, and status.')
                    ->schema([
                        Select::make('question_type')
                            ->label('Question Type')
                            ->options([
                                'mcq' => 'Multiple Choice Question (MCQ)',
                                'short_answer' => 'Short Answer',
                                'descriptive' => 'Descriptive Question',
                                'essay' => 'Essay Prompt',
                            ])
                            ->required(),

                        TextInput::make('code')
                            ->label('Question Code')
                            ->placeholder('e.g. Q-VERB-001')
                            ->maxLength(100),

                        Select::make('difficulty_level')
                            ->label('Difficulty Level')
                            ->options([
                                'easy' => 'Easy',
                                'medium' => 'Medium',
                                'hard' => 'Hard',
                            ])
                            ->default('medium')
                            ->required(),

                        Toggle::make('is_active')
                            ->label('Active Question')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Section::make('Question Content & Payload')
                    ->description('Provide the core prompt, rich solution explanation, and dynamic JSON payload data.')
                    ->schema([
                        RichEditor::make('question_text')
                            ->label('Question Prompt')
                            ->required()
                            ->columnSpanFull(),

                        KeyValue::make('payload')
                            ->label('Question Payload (JSON Configuration)')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->helperText('Store options array, correct_option_index, word limits, or rubric key themes.')
                            ->columnSpanFull(),

                        RichEditor::make('explanation')
                            ->label('Solution / Detailed Explanation')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
