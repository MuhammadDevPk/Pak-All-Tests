<?php

namespace App\Filament\Pages;

use App\Jobs\ImportQuestionsJob;
use App\Models\ContentModule;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportQuestions extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Question Bank';

    protected static ?string $navigationLabel = 'Import Questions';

    protected static ?string $title = 'Bulk Question Import';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.import-questions';

    public ?array $data = [];

    public ?string $reportKey = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Import Target')
                    ->description('Choose the exam domain and where the imported subject modules should live.')
                    ->icon(Heroicon::OutlinedCircleStack)
                    ->schema([
                        Select::make('domain_id')
                            ->label('Domain')
                            ->options(fn (): array => Domain::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->live(),
                        Radio::make('parent_mode')
                            ->label('Subject Modules')
                            ->options([
                                'create_new' => 'Create new parent module',
                                'existing' => 'Use existing module\u2019s domain',
                            ])
                            ->default('create_new')
                            ->inline()
                            ->live()
                            ->required(),
                        TextInput::make('parent_title')
                            ->label('New Parent Module Title')
                            ->default('Academic')
                            ->maxLength(120)
                            ->required()
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'create_new'),
                        Select::make('parent_module_id')
                            ->label('Existing Module')
                            ->options(fn (Get $get): array => $get('domain_id')
                                ? ContentModule::where('domain_id', $get('domain_id'))
                                    ->orderBy('sort_order')
                                    ->pluck('title', 'id')
                                    ->all()
                                : [])
                            ->searchable()
                            ->required()
                            ->visible(fn (Get $get): bool => $get('parent_mode') === 'existing'),
                    ]),
                Section::make('Question File')
                    ->description('Upload the JSON payload. Run a dry run first to preview what would be imported.')
                    ->icon(Heroicon::OutlinedDocumentArrowUp)
                    ->schema([
                        FileUpload::make('json_file')
                            ->label('Questions JSON')
                            ->acceptedFileTypes(['application/json'])
                            ->maxSize(10240)
                            ->directory('imports')
                            ->disk('local')
                            ->required(),
                        Toggle::make('dry_run')
                            ->label('Dry Run')
                            ->helperText('Validate and preview the import without writing anything to the database.')
                            ->default(true),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('startImport')
                ->label('Start Import')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->action(fn (): mixed => $this->startImport()),
        ];
    }

    public function getLastReport(): ?array
    {
        return $this->reportKey ? Cache::get($this->reportKey) : null;
    }

    public function startImport(): mixed
    {
        $data = $this->form->getState();

        try {
            $raw = Storage::disk('local')->get($data['json_file']);
        } catch (\Throwable) {
            Notification::make()
                ->title('Could not read the uploaded file')
                ->danger()
                ->send();

            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            Notification::make()
                ->title('Invalid JSON file')
                ->body('The uploaded file is not valid JSON.')
                ->danger()
                ->send();

            return null;
        }

        $validation = ImportQuestionsJob::validatePayload($decoded);
        if (! $validation['valid']) {
            Notification::make()
                ->title('Import file rejected')
                ->body(implode(' ', array_slice($validation['errors'], 0, 3)))
                ->danger()
                ->send();

            return null;
        }

        $this->reportKey = 'question_import_report_'.Str::uuid()->toString();

        $job = new ImportQuestionsJob(
            filePath: $data['json_file'],
            domainId: (int) $data['domain_id'],
            parentMode: $data['parent_mode'],
            parentModuleId: isset($data['parent_module_id']) ? (int) $data['parent_module_id'] : null,
            parentTitle: trim((string) ($data['parent_title'] ?? 'Academic')) ?: 'Academic',
            dryRun: (bool) $data['dry_run'],
            adminUserId: auth()->id(),
            reportKey: $this->reportKey,
        );

        if ($job->dryRun) {
            // Dry runs execute synchronously so the preview report is available immediately.
            ImportQuestionsJob::dispatchSync($job);

            $report = Cache::get($this->reportKey, []);
            $totals = $report['totals'] ?? ['inserted' => 0, 'duplicates_skipped' => 0, 'invalid' => 0];

            Notification::make()
                ->title('Dry run complete — nothing was written')
                ->body(sprintf(
                    '%d would be inserted, %d duplicates skipped, %d invalid.',
                    $totals['inserted'],
                    $totals['duplicates_skipped'],
                    $totals['invalid']
                ))
                ->info()
                ->send();
        } else {
            ImportQuestionsJob::dispatch($job);

            Notification::make()
                ->title('Import started')
                ->body('The questions are being imported in the background. You will be notified when it finishes.')
                ->success()
                ->send();
        }

        return null;
    }
}
