<?php

namespace App\Filament\Resources\QuestionBanks\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class QuestionBankForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Question Classification & Metadata')
                    ->description('Specify the question type, difficulty level, and developer reference code.')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('question_type')
                                ->label('Question Type')
                                ->options([
                                    'mcq' => 'Multiple Choice Question (MCQ)',
                                    'short_answer' => 'Short Answer',
                                    'descriptive' => 'Descriptive Question',
                                    'essay' => 'Essay Prompt',
                                ])
                                ->required()
                                ->native(false),

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
                                ->required()
                                ->native(false),

                            Toggle::make('is_active')
                                ->label('Active Question')
                                ->helperText('Available for test generation.')
                                ->default(true)
                                ->required(),
                        ]),
                    ]),

                Section::make('Question Prompt & Content')
                    ->description('Enter the question prompt using full rich-text formatting.')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->schema([
                        RichEditor::make('question_text')
                            ->label('Question Prompt')
                            ->placeholder('Enter the full question prompt here...')
                            ->extraInputAttributes(['style' => 'min-height: 250px;'])
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'strike',
                                'link',
                                'bulletList',
                                'orderedList',
                                'codeBlock',
                                'undo',
                                'redo',
                            ])
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Question Payload Configuration')
                    ->description('Store MCQ options, correct answer indices, essay word limits, or scoring rubrics.')
                    ->icon(Heroicon::OutlinedQueueList)
                    ->schema([
                        Textarea::make('payload')
                            ->label('Question Payload (JSON)')
                            ->helperText('Input a valid JSON object. E.g. {"options": ["Choice A", "Choice B"], "correct_option_index": 0}')
                            ->placeholder("{\n  \"options\": [\n    \"Option A\",\n    \"Option B\"\n  ],\n  \"correct_option_index\": 0\n}")
                            ->rows(8)
                            ->required()
                            ->columnSpanFull()
                            ->afterStateHydrated(fn ($state, $set) => is_array($state) ? $set('payload', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) : null)
                            ->dehydrateStateUsing(fn ($state) => json_decode($state, true))
                            ->rules([
                                fn () => function (string $attribute, $value, $fail) {
                                    if (is_string($value)) {
                                        json_decode($value);
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            $fail('The '.$attribute.' must be a valid JSON string: '.json_last_error_msg());
                                        }
                                    }
                                },
                            ]),
                    ]),

                Section::make('Solution & Explanation')
                    ->description('Detailed post-test explanation shown to candidates upon test submission.')
                    ->icon(Heroicon::OutlinedLightBulb)
                    ->collapsible()
                    ->schema([
                        RichEditor::make('explanation')
                            ->label('Post-Test Solution Explanation')
                            ->placeholder('Provide detailed explanation or step-by-step resolution...')
                            ->extraInputAttributes(['style' => 'min-height: 200px;'])
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'bulletList',
                                'orderedList',
                                'link',
                                'codeBlock',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
