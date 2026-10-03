<?php

namespace App\Filament\Pages;

use App\Jobs\ImportQuestionsJob;
use App\Jobs\LinkPmaSubjectModulesJob;
use App\Models\Domain;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property Schema $form
 */
class ImportQuestions extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static string|\UnitEnum|null $navigationGroup = 'Question Bank';

    protected static ?string $navigationLabel = 'Import Questions';

    protected static ?string $title = 'Bulk Question Import';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.import-questions';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?string $reportKey = null;

    public ?string $linkReportKey = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Import Target')
                    ->description('Choose the exam domain. One preparation module is created per subject directly under it.')
                    ->icon(Heroicon::OutlinedCircleStack)
                    ->schema([
                        Select::make('domain_id')
                            ->label('Domain')
                            ->options(fn (): array => Domain::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->default(fn (): ?int => Domain::where('code', 'PMA_INITIAL')->value('id'))
                            ->searchable()
                            ->required(),
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
                Section::make('Repair: Link Existing Questions')
                    ->description('One-time repair for questions already in the bank. Creates the five PMA subject modules under the selected domain if missing and links every PMA-ACAD question to its module. Nothing is re-imported and no vectorization jobs are dispatched.')
                    ->icon(Heroicon::OutlinedLink)
                    ->schema([
                        Toggle::make('link_dry_run')
                            ->label('Dry Run')
                            ->helperText('Preview what would be linked without writing anything to the database.')
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
            Action::make('linkModules')
                ->label('Link Questions to Modules')
                ->icon(Heroicon::OutlinedLink)
                ->color('warning')
                ->action(fn (): mixed => $this->startLink()),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLastReport(): ?array
    {
        return $this->reportKey ? Cache::get($this->reportKey) : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLastLinkReport(): ?array
    {
        return $this->linkReportKey ? Cache::get($this->linkReportKey) : null;
    }

    public function startImport(): mixed
    {
        @set_time_limit(300);

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
        $adminUserId = auth()->id() !== null ? (int) auth()->id() : null;

        if ((bool) $data['dry_run']) {
            // Dry runs execute synchronously so the preview report is available immediately.
            ImportQuestionsJob::dispatchSync(
                filePath: $data['json_file'],
                domainId: (int) $data['domain_id'],
                dryRun: true,
                adminUserId: $adminUserId,
                reportKey: $this->reportKey,
            );

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
            ImportQuestionsJob::dispatch(
                filePath: $data['json_file'],
                domainId: (int) $data['domain_id'],
                dryRun: false,
                adminUserId: $adminUserId,
                reportKey: $this->reportKey,
            );

            Notification::make()
                ->title('Import started')
                ->body('The questions are being imported in the background. You will be notified when it finishes.')
                ->success()
                ->send();
        }

        return null;
    }

    public function startLink(): mixed
    {
        @set_time_limit(300);

        $data = $this->form->getState();
        $domainId = (int) $data['domain_id'];

        $domainActive = Domain::where('id', $domainId)->where('is_active', true)->exists();
        if (! $domainActive) {
            Notification::make()
                ->title('Domain not available')
                ->body('The selected domain was not found or is inactive.')
                ->danger()
                ->send();

            return null;
        }

        $this->linkReportKey = 'pma_link_report_'.Str::uuid()->toString();
        $adminUserId = auth()->id() !== null ? (int) auth()->id() : null;

        if ((bool) ($data['link_dry_run'] ?? true)) {
            // Dry runs execute synchronously so the preview report is available immediately.
            LinkPmaSubjectModulesJob::dispatchSync(
                domainId: $domainId,
                dryRun: true,
                adminUserId: $adminUserId,
                reportKey: $this->linkReportKey,
            );

            $report = Cache::get($this->linkReportKey, []);
            $totals = $report['totals'] ?? ['linked' => 0, 'already_linked' => 0, 'question_not_found' => 0];

            Notification::make()
                ->title('Link dry run complete — nothing was written')
                ->body(sprintf(
                    '%d would be linked, %d already linked, %d not found.',
                    $totals['linked'],
                    $totals['already_linked'],
                    $totals['question_not_found']
                ))
                ->info()
                ->send();
        } else {
            LinkPmaSubjectModulesJob::dispatch(
                domainId: $domainId,
                dryRun: false,
                adminUserId: $adminUserId,
                reportKey: $this->linkReportKey,
            );

            Notification::make()
                ->title('Linking started')
                ->body('The questions are being linked to their subject modules in the background. You will be notified when it finishes.')
                ->success()
                ->send();
        }

        return null;
    }
}
