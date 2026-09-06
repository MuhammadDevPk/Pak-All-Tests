<?php

namespace App\Jobs;

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\QuestionBank;
use App\Services\Ai\KnowledgeVectorizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VectorizeDomainKnowledgeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?int $domainId = null,
        public ?int $moduleId = null,
        public ?int $questionId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(KnowledgeVectorizationService $service): void
    {
        if ($this->domainId) {
            $domain = Domain::find($this->domainId);
            if ($domain) {
                $service->vectorizeDomain($domain);
            }
        }

        if ($this->moduleId) {
            $module = ContentModule::find($this->moduleId);
            if ($module) {
                $service->vectorizeModule($module);
            }
        }

        if ($this->questionId) {
            $question = QuestionBank::find($this->questionId);
            if ($question) {
                $service->vectorizeQuestion($question);
            }
        }
    }
}
