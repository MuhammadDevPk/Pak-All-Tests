<?php

namespace App\Filament\Resources\ContentModules\Schemas;

use App\Models\ContentModule;
use App\Models\Domain;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ContentModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module Core Settings')
                    ->description('Categorize and define basic module properties and domain linking.')
                    ->icon(Heroicon::OutlinedFolder)
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('domain_id')
                                ->label('Domain')
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
                                ->live()
                                ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                    if ($operation === 'create' && $state) {
                                        $next = (ContentModule::where('domain_id', $state)->max('sort_order') ?? 0) + 1;
                                        $set('sort_order', $next);
                                    }
                                })
                                ->required()
                                ->placeholder('Select Domain'),

                            Select::make('module_type')
                                ->label('Module Type')
                                ->options([
                                    'preparation' => 'Preparation Module',
                                    'mock_test' => 'Mock Test',
                                    'subjective' => 'Subjective / Essay Prompt',
                                    'pdf_repo' => 'PDF / Document Repository',
                                ])
                                ->required()
                                ->live()
                                ->native(false),

                            TextInput::make('dashboard_section')
                                ->label('Dashboard Section')
                                ->placeholder('e.g. academic, physical, medical, interview')
                                ->helperText('Categorizes which dashboard section/phase this module belongs to.')
                                ->datalist(fn (): array => ContentModule::query()
                                    ->whereNotNull('dashboard_section')
                                    ->distinct()
                                    ->pluck('dashboard_section')
                                    ->filter()
                                    ->values()
                                    ->all()
                                ),

                            Select::make('display_mode')
                                ->label('Display / Player Mode')
                                ->options([
                                    'auto' => '⚡ Auto-Detect (based on questions)',
                                    'study_qa' => '📖 Study & Q&A Cards (Guidelines, FAQs, Tips)',
                                    'mcq_test' => '🎯 MCQ Practice & Exam Player',
                                    'essay' => '✍️ Essay & Subjective Submission',
                                    'pdf_repo' => '📑 PDF / Document Viewer',
                                ])
                                ->default('auto')
                                ->helperText('Determines the candidate-facing interactive player experience.')
                                ->native(false),

                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g. Verbal Intelligence Notes & PDF')
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                    if ($operation === 'create') {
                                        $set('slug', Str::slug($state));
                                    }
                                }),

                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('e.g. verbal-intelligence-notes-pdf'),

                            TextInput::make('sort_order')
                                ->label('Sort Order Index')
                                ->numeric()
                                ->default(1)
                                ->helperText('Sequence index in candidate dashboards. Auto-calculated for the chosen domain.')
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Module Active Status')
                                ->helperText('Control whether this module is visible to students.')
                                ->default(true)
                                ->required(),
                        ]),
                    ]),

                Section::make('Attached Documents & Resources')
                    ->description('Upload or link PDF study guides, formula books, past papers, or individual files under this module.')
                    ->icon(Heroicon::OutlinedDocumentArrowUp)
                    ->schema([
                        Repeater::make('files')
                            ->label('Attached Files / Documents')
                            ->addActionLabel('+ Add File / Document')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => trim($state['title'] ?? '') ?: 'Untitled Document')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('title')
                                        ->label('Individual File Title')
                                        ->placeholder('e.g. Part 1: Verbal Formula Book')
                                        ->required()
                                        ->columnSpan(2),

                                    Select::make('source_type')
                                        ->label('Source Type')
                                        ->options([
                                            'upload' => '📁 Upload File',
                                            'url' => '🔗 External / Drive URL',
                                        ])
                                        ->default('upload')
                                        ->live(),
                                ]),

                                FileUpload::make('document_file')
                                    ->label('Upload PDF / Document File')
                                    ->disk('public')
                                    ->directory('content-modules/documents')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'image/jpeg',
                                        'image/png',
                                        'image/webp',
                                    ])
                                    ->maxSize(51200) // 50MB max file size
                                    ->downloadable()
                                    ->openable()
                                    ->hidden(fn (Get $get) => $get('source_type') === 'url'),

                                TextInput::make('file_url')
                                    ->label('Document / PDF File URL')
                                    ->placeholder('e.g. https://drive.google.com/file/d/.../preview or /storage/...')
                                    ->helperText('Paste a direct PDF link, Google Drive link, or external document URL.')
                                    ->hidden(fn (Get $get) => $get('source_type') === 'upload')
                                    ->columnSpanFull(),

                                Grid::make(2)->schema([
                                    TextInput::make('file_size')
                                        ->label('File Size (Optional)')
                                        ->placeholder('e.g. 2.4 MB'),

                                    TextInput::make('file_type')
                                        ->label('File Format')
                                        ->default('PDF Document')
                                        ->placeholder('e.g. PDF Document, Word Doc'),
                                ]),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Content Metadata & Configuration')
                    ->description('Specify test parameters like duration, passing score, or additional JSON attributes.')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->schema([
                        Textarea::make('content_meta')
                            ->label('Module Metadata (JSON)')
                            ->helperText('Defines duration_minutes, passing_score, file_url, and custom variables.')
                            ->placeholder("{\n  \"duration_minutes\": 20,\n  \"passing_score\": 60,\n  \"description\": \"Study notes and PDF\"\n}")
                            ->rows(6)
                            ->columnSpanFull()
                            ->afterStateHydrated(fn ($state, $set) => is_array($state) ? $set('content_meta', json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) : null)
                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : ($state ?? []))
                            ->rules([
                                fn () => function (string $attribute, $value, $fail) {
                                    if (is_string($value) && trim($value) !== '') {
                                        json_decode($value);
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            $fail('The '.$attribute.' must be a valid JSON string: '.json_last_error_msg());
                                        }
                                    }
                                },
                            ]),
                    ]),
            ]);
    }
}
