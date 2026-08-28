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
                                    'pdf_repo' => 'PDF / Document Repository',
                                ])
                                ->required()
                                ->live()
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
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record && is_array($record->content_meta)) {
                                    $meta = $record->content_meta;
                                    if (! empty($meta['files']) && is_array($meta['files'])) {
                                        $items = [];
                                        foreach ($meta['files'] as $f) {
                                            $url = $f['file_url'] ?? '';
                                            $isUpload = str_starts_with($url, '/storage/');
                                            $filePath = $isUpload ? str_replace('/storage/', '', $url) : null;
                                            $items[] = [
                                                'title' => $f['title'] ?? '',
                                                'source_type' => $f['source_type'] ?? ($isUpload ? 'upload' : 'url'),
                                                'document_file' => $filePath ? [(string) Str::uuid() => $filePath] : [],
                                                'file_url' => $url,
                                                'file_type' => $f['file_type'] ?? 'PDF Document',
                                                'file_size' => $f['file_size'] ?? '',
                                            ];
                                        }
                                        $component->state($items);
                                    } elseif (! empty($meta['file_url'])) {
                                        $url = $meta['file_url'];
                                        $isUpload = str_starts_with($url, '/storage/');
                                        $filePath = $isUpload ? str_replace('/storage/', '', $url) : null;
                                        $component->state([[
                                            'title' => $record->title,
                                            'source_type' => $isUpload ? 'upload' : 'url',
                                            'document_file' => $filePath ? [(string) Str::uuid() => $filePath] : [],
                                            'file_url' => $url,
                                            'file_type' => $meta['file_type'] ?? 'PDF Document',
                                            'file_size' => $meta['file_size'] ?? '',
                                        ]]);
                                    }
                                }
                            })
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
                                    ->live()
                                    ->hidden(fn (Get $get) => $get('source_type') === 'url')
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $filePath = is_array($state) ? reset($state) : $state;
                                            $set('file_url', '/storage/'.ltrim($filePath, '/'));
                                        }
                                    }),

                                TextInput::make('file_url')
                                    ->label('Document / PDF File URL')
                                    ->placeholder('e.g. https://drive.google.com/file/d/.../preview or /storage/...')
                                    ->helperText('Paste a direct PDF link, Google Drive link, or external document URL.')
                                    ->hidden(fn (Get $get) => $get('source_type') === 'upload' && blank($get('file_url')))
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
                            ->dehydrateStateUsing(function ($state, callable $get) {
                                $meta = is_string($state) ? json_decode($state, true) : ($state ?? []);
                                if (! is_array($meta)) {
                                    $meta = [];
                                }

                                $files = $get('files');
                                if (is_array($files) && count($files) > 0) {
                                    $cleanFiles = [];
                                    foreach ($files as $f) {
                                        $fileUrl = $f['file_url'] ?? null;
                                        if (! empty($f['document_file'])) {
                                            $filePath = is_array($f['document_file']) ? reset($f['document_file']) : $f['document_file'];
                                            $fileUrl = '/storage/'.ltrim($filePath, '/');
                                        }

                                        if (! empty($fileUrl) || ! empty($f['title'])) {
                                            $cleanFiles[] = [
                                                'title' => $f['title'] ?? 'Document',
                                                'file_url' => $fileUrl,
                                                'file_type' => $f['file_type'] ?? 'PDF Document',
                                                'file_size' => $f['file_size'] ?? null,
                                                'source_type' => $f['source_type'] ?? 'upload',
                                            ];
                                        }
                                    }

                                    $meta['files'] = $cleanFiles;
                                    if (count($cleanFiles) > 0) {
                                        $meta['file_url'] = $cleanFiles[0]['file_url'];
                                        $meta['file_type'] = $cleanFiles[0]['file_type'];
                                        $meta['file_size'] = $cleanFiles[0]['file_size'];
                                    }
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
