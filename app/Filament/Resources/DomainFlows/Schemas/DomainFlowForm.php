<?php

namespace App\Filament\Resources\DomainFlows\Schemas;

use App\Models\ContentModule;
use App\Models\Domain;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;

class DomainFlowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // ── Identity & Versioning ──────────────────────────────────────
                Section::make('Flow Identity & Versioning')
                    ->description('Bind this server-driven UI configuration to an exam domain.')
                    ->icon(Heroicon::OutlinedAdjustmentsVertical)
                    ->schema([
                        Grid::make(3)->schema([
                            Select::make('domain_id')
                                ->label('Target Domain')
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
                                ->unique(ignoreRecord: true)
                                ->required()
                                ->placeholder('Select Exam Domain'),

                            TextInput::make('version')
                                ->label('Schema Version')
                                ->numeric()
                                ->default(1)
                                ->required(),

                            Toggle::make('is_active')
                                ->label('Active Schema')
                                ->helperText('Toggle to activate this SDUI configuration.')
                                ->default(true)
                                ->required(),
                        ]),
                    ]),

                // ── Visual Phase Builder ───────────────────────────────────────
                Section::make('Visual Phase Builder')
                    ->description('Build the candidate dashboard phase-by-phase. Each phase becomes a step in the navigation stepper.')
                    ->icon(Heroicon::OutlinedWindow)
                    ->schema([
                        Repeater::make('phases')
                            ->label(false)
                            ->addActionLabel('+ Add Phase')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): string => trim(($state['title'] ?? '')) ?: 'Untitled Phase')
                            ->schema(static::phaseFields())
                            ->columnSpanFull(),
                    ]),

                // ── Pedagogical & Gating Rules ─────────────────────────────────
                Section::make('Pedagogical & Gating Rules')
                    ->description('Define passing percentages and time limits for this domain.')
                    ->icon(Heroicon::OutlinedAcademicCap)
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('passing_percentage')
                                ->label('Passing Percentage (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->placeholder('e.g. 60')
                                ->helperText('Minimum score required to pass. Leave blank to disable.'),

                            TextInput::make('time_limit_minutes')
                                ->label('Time Limit (minutes)')
                                ->numeric()
                                ->minValue(1)
                                ->placeholder('e.g. 45')
                                ->helperText('Maximum duration per attempt. Leave blank for no limit.'),
                        ]),
                    ]),
            ]);
    }

    // ─── Phase-level fields ────────────────────────────────────────────────────

    private static function phaseFields(): array
    {
        return [
            Grid::make(2)->schema([
                TextInput::make('title')
                    ->label('Phase Title')
                    ->required()
                    ->placeholder('e.g. Intelligence Testing'),

                TextInput::make('subtitle')
                    ->label('Subtitle')
                    ->placeholder('e.g. Verbal & Non-Verbal MCQs'),
            ]),

            Select::make('content_type')
                ->label('Phase Content Type')
                ->options([
                    'TabbedLayout' => '🗂  Tabbed Layout  — nest multiple tabs',
                    'SubjectGrid'  => '⊞  Subject Grid   — test/practice cards',
                    'ResourceList' => '📄  Resource List  — handouts & documents',
                    'McqPlayer'    => '🧠  MCQ Player     — direct MCQ module',
                ])
                ->required()
                ->live()
                ->default('SubjectGrid'),

            // ── TabbedLayout (Phase Level) ────────────────────────────────
            TextInput::make('default_tab')
                ->label('Default Tab ID')
                ->placeholder('e.g. verbal')
                ->helperText('ID of the tab that is active on first load.')
                ->hidden(fn (Get $get) => $get('content_type') !== 'TabbedLayout'),

            Repeater::make('tabs')
                ->label('Tabs')
                ->addActionLabel('+ Add Tab')
                ->reorderable()
                ->collapsible()
                ->itemLabel(fn (array $state): string => trim($state['label'] ?? '') ?: 'Untitled Tab')
                ->schema(static::tabFields())
                ->hidden(fn (Get $get) => $get('content_type') !== 'TabbedLayout')
                ->columnSpanFull(),

            // ── Phase-level direct content fields (when not TabbedLayout) ──
            ...static::contentItemFields(),
        ];
    }

    // ─── Tab-level fields ──────────────────────────────────────────────────────

    private static function tabFields(): array
    {
        return [
            Grid::make(3)->schema([
                TextInput::make('id')
                    ->label('Tab ID')
                    ->required()
                    ->placeholder('e.g. verbal'),
                TextInput::make('label')
                    ->label('Tab Label')
                    ->required()
                    ->placeholder('e.g. Verbal Intelligence'),
                TextInput::make('badge')
                    ->label('Badge')
                    ->placeholder('e.g. Active'),
            ]),

            Select::make('content_type')
                ->label('Tab Content Type')
                ->options([
                    'SubjectGrid'  => '⊞  Subject Grid   — test/practice cards',
                    'ResourceList' => '📄  Resource List  — handouts & documents',
                    'McqPlayer'    => '🧠  MCQ Player     — direct MCQ module',
                ])
                ->required()
                ->live()
                ->default('SubjectGrid'),

            ...static::contentItemFields(),
        ];
    }

    // ─── Content items fields (SubjectGrid, ResourceList, McqPlayer) ───────────

    private static function contentItemFields(): array
    {
        return [
            // ── SubjectGrid column count ──────────────────────────────────
            Select::make('columns')
                ->label('Grid Columns')
                ->options([1 => '1 Column', 2 => '2 Columns', 3 => '3 Columns', 4 => '4 Columns'])
                ->default(2)
                ->hidden(fn (Get $get) => $get('content_type') !== 'SubjectGrid'),

            // ── ResourceList section title ────────────────────────────────
            TextInput::make('resource_title')
                ->label('Section Title')
                ->placeholder('e.g. PMA Handouts & Notes')
                ->helperText('Shown above the list of downloadable files.')
                ->hidden(fn (Get $get) => $get('content_type') !== 'ResourceList'),

            // ── Shared source mode (SubjectGrid + ResourceList) ───────────
            Select::make('source_mode')
                ->label('Item Source')
                ->options([
                    'moduleQuery' => '⚡ Auto — pull from Content Modules (recommended)',
                    'manual'      => '✋ Manual — pin specific modules by hand',
                ])
                ->default('moduleQuery')
                ->live()
                ->hidden(fn (Get $get) => !in_array($get('content_type'), ['SubjectGrid', 'ResourceList'])),

            // ── moduleQuery config ────────────────────────────────────────
            Grid::make(3)->schema([
                Select::make('module_type')
                    ->label('Module Type Filter')
                    ->options([
                        'preparation' => 'Preparation',
                        'mock_test'   => 'Mock Test',
                        'pdf_repo'    => 'PDF Repository',
                        'subjective'  => 'Subjective',
                    ])
                    ->required()
                    ->default('preparation'),

                TextInput::make('slug_contains')
                    ->label('Slug Contains (optional)')
                    ->placeholder('e.g. verbal')
                    ->helperText('Filter by slug substring.'),

                Select::make('merge')
                    ->label('Merge Mode')
                    ->options([
                        'replace' => 'Replace — query replaces manual items',
                        'append'  => 'Append  — query added after manual items',
                        'prepend' => 'Prepend — query added before manual items',
                    ])
                    ->default('replace'),
            ])->hidden(fn (Get $get) => $get('source_mode') !== 'moduleQuery'
                || !in_array($get('content_type'), ['SubjectGrid', 'ResourceList'])),

            // ── Manual items repeater ─────────────────────────────────────
            Repeater::make('manual_items')
                ->label('Pinned Items')
                ->addActionLabel('+ Pin a Module')
                ->reorderable()
                ->schema([
                    Select::make('module_id')
                        ->label('Content Module')
                        ->options(fn () => ContentModule::where('is_active', true)
                            ->orderBy('title')
                            ->pluck('title', 'id')
                            ->all())
                        ->searchable()
                        ->required(),

                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->label('Override Title')
                            ->placeholder('Leave blank to use module title'),
                        TextInput::make('description')
                            ->label('Description')
                            ->placeholder('Short description shown on the card'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('action_label')
                            ->label('Button Label')
                            ->placeholder('e.g. Start Practice')
                            ->default('Start'),
                        TextInput::make('file_size')
                            ->label('File Size')
                            ->placeholder('e.g. 3.4 MB  (ResourceList only)'),
                    ]),
                ])
                ->hidden(fn (Get $get) => $get('source_mode') !== 'manual'
                    || !in_array($get('content_type'), ['SubjectGrid', 'ResourceList']))
                ->columnSpanFull(),

            // ── McqPlayer: direct module picker ──────────────────────────
            Select::make('mcq_module_id')
                ->label('MCQ Content Module')
                ->options(fn () => ContentModule::where('is_active', true)
                    ->where('module_type', 'preparation')
                    ->orderBy('title')
                    ->pluck('title', 'id')
                    ->all())
                ->searchable()
                ->required()
                ->helperText('The MCQ test session will load questions from this module.')
                ->hidden(fn (Get $get) => $get('content_type') !== 'McqPlayer'),
        ];
    }
}
