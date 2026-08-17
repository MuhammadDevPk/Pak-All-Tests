<?php

namespace App\Filament\Resources\ContentModules\Schemas;

use App\Models\Domain;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                                ->required()
                                ->placeholder('Select Domain'),

                            Select::make('module_type')
                                ->label('Module Type')
                                ->options([
                                    'preparation' => 'Preparation Module',
                                    'mock_test' => 'Mock Test',
                                    'pdf_repo' => 'PDF / Document Repository',
                                ])
                                ->required()
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
                                ->default(0)
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Module Active Status')
                                ->helperText('Control whether this module is visible to students.')
                                ->default(true)
                                ->required(),
                        ]),
                    ]),

                Section::make('Document & PDF File Upload')
                    ->description('Upload PDF study guides, syllabus books, past papers, or document resources for candidate access.')
                    ->icon(Heroicon::OutlinedDocumentArrowUp)
                    ->schema([
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
                            ->live()
                            ->dehydrated(false)
                            ->helperText('Upload a PDF, Word document, or image file. The file URL will be saved to content_meta.')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && is_array($record->content_meta)) {
                                    $url = $record->content_meta['file_url'] ?? $record->content_meta['file_path'] ?? null;
                                    if ($url && str_contains($url, '/storage/')) {
                                        $component->state(str_replace('/storage/', '', $url));
                                    }
                                }
                            })
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $filePath = is_array($state) ? reset($state) : $state;
                                    $set('file_url', '/storage/'.ltrim($filePath, '/'));
                                }
                            }),

                        TextInput::make('file_url')
                            ->label('Document / PDF File URL')
                            ->placeholder('e.g. /storage/content-modules/documents/verbal-notes.pdf or https://...')
                            ->dehydrated(false)
                            ->helperText('Automatically populated when uploading a file above, or enter an external URL.')
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && is_array($record->content_meta)) {
                                    $component->state($record->content_meta['file_url'] ?? $record->content_meta['file_path'] ?? null);
                                }
                            }),
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
                            ->dehydrateStateUsing(function ($state, callable $get) {
                                $meta = is_string($state) ? json_decode($state, true) : ($state ?? []);
                                if (!is_array($meta)) {
                                    $meta = [];
                                }

                                $docFile = $get('document_file');
                                $fileUrl = $get('file_url');

                                if ($docFile) {
                                    $filePath = is_array($docFile) ? reset($docFile) : $docFile;
                                    $meta['file_url'] = '/storage/'.ltrim($filePath, '/');
                                } elseif ($fileUrl) {
                                    $meta['file_url'] = $fileUrl;
                                }

                                return $meta;
                            })
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
