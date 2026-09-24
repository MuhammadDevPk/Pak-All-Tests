<?php

namespace App\Filament\Resources\QuestionBanks\Tables;

use App\Actions\QuestionBank\StoreGeneratedQuestionsAction;
use App\Models\Domain;
use App\Models\QuestionBank;
use App\Services\Ai\KnowledgeVectorizationService;
use App\Services\Ai\McqGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class QuestionBanksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->query(QuestionBank::query())
            ->paginationMode(PaginationMode::Simple)
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('question_type')
                    ->label('Type')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('question_text')
                    ->label('Question Prompt')
                    ->html()
                    ->limit(60)
                    ->searchable(),

                TextColumn::make('difficulty_level')
                    ->label('Difficulty')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'easy' => 'success',
                        'medium' => 'warning',
                        'hard' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('vectorize')
                    ->label('Vectorize')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('success')
                    ->action(function (QuestionBank $record, KnowledgeVectorizationService $service) {
                        $created = $service->vectorizeQuestion($record);
                        if (empty($created)) {
                            Notification::make()
                                ->title('No Associated Domains')
                                ->body('Attach this question to a content module under a domain before vectorizing.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Question Vectorized')
                            ->body('Question vectors successfully generated and stored.')
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->headerActions([
                Action::make('importQuestions')
                    ->label('✨ Import & Generate MCQs')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->modalHeading('Universal Question Ingestion & Generation Engine')
                    ->modalDescription('Zero-token direct parsing for complete MCQs, or AI generation for missing options/topics.')
                    ->modalWidth('4xl')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('import_mode')
                                ->label('Ingestion Mode')
                                ->options([
                                    'direct_mcq' => '⚡ Direct Import (0 Tokens — Complete Questions with Options & Answer)',
                                    'ai_distractor' => '🧠 AI Distractor Generator (Questions & Answers — AI Generates 3 Options)',
                                    'ai_topic' => '📚 AI Full Generator (Topic / Syllabus Notes — AI Drafts Complete MCQs)',
                                ])
                                ->default('direct_mcq')
                                ->live()
                                ->required()
                                ->columnSpanFull(),

                            Select::make('target_domain_ids')
                                ->label('Target Exam / Domain(s)')
                                ->helperText('Select all courses this question set applies to (e.g. PMA, CSS, Navy).')
                                ->options(fn (): array => Cache::remember(
                                    'domains_select_options',
                                    3600,
                                    fn () => Domain::query()->orderBy('name')->pluck('name', 'id')->all()
                                ))
                                ->multiple()
                                ->searchable()
                                ->required(),

                            Select::make('module_strategy')
                                ->label('Module Distribution Strategy')
                                ->options([
                                    'auto_create' => '✨ Auto-Create New Mock Test in Selected Domains',
                                    'pool' => '📦 Save to Global Question Bank Pool (Unattached)',
                                ])
                                ->default('auto_create')
                                ->live()
                                ->required(),

                            TextInput::make('new_module_title')
                                ->label('New Mock Test Module Title')
                                ->placeholder('e.g. General Knowledge Past Papers 2026')
                                ->default('Imported Practice Test')
                                ->required(fn (Get $get): bool => $get('module_strategy') === 'auto_create')
                                ->visible(fn (Get $get): bool => $get('module_strategy') === 'auto_create')
                                ->columnSpanFull(),
                        ]),

                        Section::make('Content Input')
                            ->description('Paste your raw question text, Q&A list, or chapter notes below.')
                            ->schema([
                                Textarea::make('raw_text')
                                    ->label('Raw Content / Notes')
                                    ->placeholder(fn (Get $get): string => match ($get('import_mode')) {
                                        'direct_mcq' => "1. What is the capital of Australia?\nA) Sydney\nB) Melbourne\nC) Canberra\nD) Brisbane\nAnswer: C\n\n2. Which year was the Pakistan Resolution passed?\nA) 1930  B) 1940  C) 1947  D) 1956\nAnswer: B",
                                        'ai_distractor' => "Q: What is the capital of Australia?\nA: Canberra\n\nQ: In which Hijri year was Treaty of Hudaybiyyah signed?\nA: 6 AH",
                                        default => "Enter chapter notes or a topic prompt (e.g. 'Generate 10 questions on the Rivers of Pakistan and their tributaries for PMA Initial Test').",
                                    })
                                    ->rows(10)
                                    ->columnSpanFull(),

                                FileUpload::make('document_file')
                                    ->label('Or Upload File (.txt, .docx, .csv)')
                                    ->disk('public')
                                    ->directory('temp-imports')
                                    ->acceptedFileTypes([
                                        'text/plain',
                                        'text/csv',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                    ])
                                    ->maxSize(10240)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->action(function (
                        array $data,
                        McqGeneratorService $generatorService,
                        StoreGeneratedQuestionsAction $storeAction
                    ) {
                        $rawText = (string) ($data['raw_text'] ?? '');
                        if (! empty($data['document_file'])) {
                            $uploaded = is_array($data['document_file']) ? reset($data['document_file']) : $data['document_file'];
                            $filePath = storage_path('app/public/'.$uploaded);
                            $extracted = $generatorService->extractTextFromFilePath($filePath);
                            if ($extracted !== '') {
                                $rawText = $extracted;
                            }
                        }

                        if (trim($rawText) === '') {
                            Notification::make()->title('No Content Provided')->warning()->send();

                            return;
                        }

                        $questions = [];
                        $mode = $data['import_mode'] ?? 'direct_mcq';

                        if ($mode === 'direct_mcq') {
                            $parsed = $generatorService->parseCompleteMcqs($rawText);
                            $questions = $parsed['questions'];

                            if (! empty($parsed['unparsed_lines'])) {
                                $count = count($parsed['unparsed_lines']);
                                Notification::make()
                                    ->title("Notice: {$count} Unrecognized Lines")
                                    ->body('Some lines could not be parsed as options or questions. Check question formatting.')
                                    ->warning()
                                    ->send();
                            }
                        } elseif ($mode === 'ai_distractor') {
                            $qaPairs = $generatorService->parseQaPairs($rawText);
                            $questions = $generatorService->generateDistractorsForPairs($qaPairs);
                        } else {
                            $questions = $generatorService->generateFromTopicOrText($rawText, 10);
                        }

                        if (empty($questions)) {
                            Notification::make()->title('No Questions Could Be Extracted')->danger()->send();

                            return;
                        }

                        $targetDomainIds = array_map('intval', $data['target_domain_ids'] ?? []);
                        $moduleStrategy = $data['module_strategy'] ?? 'auto_create';
                        $newModuleTitle = $data['new_module_title'] ?? null;

                        $result = $storeAction->execute(
                            questions: $questions,
                            targetDomainIds: $targetDomainIds,
                            moduleStrategy: $moduleStrategy,
                            newModuleTitle: $newModuleTitle
                        );

                        Notification::make()
                            ->title('Questions Ingested Successfully')
                            ->body("Saved {$result['inserted']} new questions ({$result['reused']} duplicates reused) across {$result['total']} instances.")
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
