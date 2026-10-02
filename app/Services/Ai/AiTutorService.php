<?php

namespace App\Services\Ai;

use App\Models\ContentModule;
use App\Models\Domain;
use App\Models\DomainChatMessage;
use App\Models\DomainKnowledgeEmbedding;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Support\Collection;

class AiTutorService
{
    public function __construct(
        protected KnowledgeVectorizationService $vectorizer,
        protected AiCompletionService $completion
    ) {}

    /**
     * Retrieve recent conversation history for candidate and domain.
     *
     * @return Collection<int, array{id: int, role: string, content: string, createdAt: string}>
     */
    public function getHistory(User $user, Domain $domain, int $limit = 40): Collection
    {
        return DomainChatMessage::forUserAndDomain($user->id, $domain->id)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (DomainChatMessage $msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'createdAt' => $msg->created_at?->diffForHumans() ?? 'Just now',
            ]);
    }

    /**
     * Clear candidate's conversation history for a domain.
     */
    public function clearHistory(User $user, Domain $domain): void
    {
        DomainChatMessage::where('user_id', $user->id)
            ->where('domain_id', $domain->id)
            ->delete();
    }

    /**
     * Ask a question, retrieve verified knowledge from pgvector, and stream Socratic response.
     *
     * @param  array{active_module_id?: int|null, active_question_id?: int|null, active_question_text?: string|null}  $context
     * @param  (callable(string): void)|null  $onToken
     */
    public function ask(
        User $user,
        Domain $domain,
        string $userMessage,
        array $context = [],
        ?callable $onToken = null
    ): string {
        // 1. Persist user message
        DomainChatMessage::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'role' => 'user',
            'content' => $userMessage,
            'context_metadata' => $context,
        ]);

        // 2. Perform RAG vector similarity search in PostgreSQL
        $knowledgeChunks = $this->vectorizer->findSimilar(
            domainId: $domain->id,
            queryText: $userMessage,
            limit: 3,
            includeHierarchy: true
        );

        // 3. Construct Socratic System Prompt
        $systemPrompt = $this->buildSystemPrompt($domain, $knowledgeChunks, $context);

        // 4. Retrieve recent message history (last 10 messages for conversation context)
        $recentMessages = DomainChatMessage::forUserAndDomain($user->id, $domain->id)
            ->latest('id')
            ->limit(10)
            ->get()
            ->reverse()
            ->map(fn ($msg) => ['role' => $msg->role, 'content' => $msg->content])
            ->values()
            ->all();

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ...$recentMessages,
        ];

        // 5. Stream or complete response
        $assistantContent = '';
        $callback = function (string $token) use (&$assistantContent, $onToken) {
            $assistantContent .= $token;
            if ($onToken) {
                $onToken($token);
            }
        };

        $this->completion->stream($messages, $callback);

        // 6. Persist assistant response
        DomainChatMessage::create([
            'user_id' => $user->id,
            'domain_id' => $domain->id,
            'role' => 'assistant',
            'content' => $assistantContent,
            'context_metadata' => [
                'sources' => $knowledgeChunks->map(fn ($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'similarity' => round((float) ($c->similarity ?? 0.0), 3),
                ])->all(),
            ],
        ]);

        return $assistantContent;
    }

    /**
     * Build domain-specific Socratic system prompt with ground-truth pgvector chunks and exam expertise.
     *
     * @param  Collection<int, DomainKnowledgeEmbedding>  $knowledgeChunks
     * @param  array<string, mixed>  $context
     */
    protected function buildSystemPrompt(Domain $domain, Collection $knowledgeChunks, array $context): string
    {
        $domainSlug = strtolower($domain->slug ?? '');
        $domainName = $domain->name ?? 'Competitive Examination';

        $lines = [
            "You are the authoritative, inspiring AI Exam Mentor dedicated to {$domainName} on Pak-All-Tests.",
            'Your mission is to guide Pakistani aspirants to success through clarity, strategic discipline, and verified syllabus knowledge.',
            '',
            'CONVERSATION & PEDAGOGY DIRECTIVES:',
            '1. GREETINGS & INTRODUCTIONS:',
            '   - If the candidate greets you (e.g. "hi", "hello", "salam", "aoa"), respond warmly, respectfully, and succinctly: introduce yourself as their dedicated AI Exam Mentor for this course, and ask what topic, syllabus area, or question they would like to work on today. Never give unsolicited problem hints or say you are searching the database on simple greetings.',
            '',
            '2. EXAM STRUCTURE & COURSE ADVISING:',
            '   - If the candidate asks about the exam itself (e.g. "what is initial?", "what are WT and SRT?", "how to prepare academics?", "eligibility criteria", "when are tests held?"):',
            '   - Provide an authoritative, structured, and comprehensive answer with bulleted points and clear explanations of each phase, timing, marking criteria, and high-yield preparation strategies.',
            '',
            '3. PROBLEM SOLVING & SOCRATIC COACHING:',
            '   - If the candidate is solving an MCQ or problem (e.g. sequence series like "2, 4, 8, 16, ?", analogies, verbal intelligence, or an active test question on screen):',
            '   - Level 1: Point out the core relationship or pattern to notice.',
            '   - Level 2: Walk through step-by-step reasoning or elimination of distractors.',
            '   - Level 3: If they ask for the solution or explanation, give the complete conceptual breakdown and mathematical formula.',
            '',
            '4. TONE & FORMATTING:',
            '   - Professional, authoritative, encouraging, academic, and institutionally precise.',
            '   - Use clean GitHub-flavored markdown (bold key terms, numbered steps, bullet points).',
            '   - Language: Seamlessly reply in the language the candidate used (English, Urdu, or Roman Urdu).',
        ];

        // Specific Domain Deep Knowledge Injection
        if (str_contains($domainSlug, 'pma') || str_contains($domainSlug, 'army') || str_contains($domainSlug, 'armed')) {
            $lines[] = '';
            $lines[] = 'INSTITUTIONAL DOMAIN KNOWLEDGE (PMA LONG COURSE & ARMED FORCES):';
            $lines[] = '- "Initial Test": The preliminary screening phase conducted at Army Selection and Recruitment Centers (AS&RC). It consists of:';
            $lines[] = '   1. Verbal Intelligence Test (84 MCQs in 30 mins: analogies, series, coding, odd-man-out).';
            $lines[] = '   2. Non-Verbal Intelligence Test (64 MCQs in 30 mins: pattern completion, matrices, rotations).';
            $lines[] = '   3. Academic Test (50 MCQs in 30 mins: English grammar/vocabulary, Mathematics, Pakistan Studies, Islamic Studies, and General Knowledge).';
            $lines[] = '   4. Physical Fitness Test (1.6 km run in 8 minutes, 3 chin-ups, 15 push-ups, 20 sit-ups, 7\'4" ditch crossing).';
            $lines[] = '   5. Initial Medical Examination and Initial Interview by the AS&RC panel.';
            $lines[] = '- Candidates who qualify the Initial Test are issued call letters for the Inter-Services Selection Board (ISSB).';
            $lines[] = '- "ISSB" Tests: 4-day psychological and outdoor evaluation:';
            $lines[] = '   * WAT (Word Association Test): 60 English words flashed for 10 seconds each; candidates write spontaneous, positive, natural sentences.';
            $lines[] = '   * SRT (Situation Reaction Test): 10 real-world high-stakes crisis scenarios; candidates write immediate decisive reactions in 10 minutes.';
            $lines[] = '   * TAT (Thematic Apperception Test): Picture and pointer stories.';
            $lines[] = '   * GTO Tasks (Group Discussion, Lecturette, Progressive/Half/Command/Final Group Tasks, Individual Obstacles) and Deputy President Interview.';
            $lines[] = '- Intakes: PMA Long Course is conducted twice a year (e.g. 153, 154, 155, 156, etc.), with registrations in Spring (April/May) and Autumn (October/November).';
        } elseif (str_contains($domainSlug, 'css')) {
            $lines[] = '';
            $lines[] = 'INSTITUTIONAL DOMAIN KNOWLEDGE (CSS COMPETITIVE EXAMINATION - FPSC):';
            $lines[] = '- CSS comprises:';
            $lines[] = '   1. MPT (MCQ-Based Preliminary Test) of 200 marks (passing threshold 33%).';
            $lines[] = '   2. Written Examination (1200 marks total: 6 Compulsory subjects [English Essay, English Precis & Composition, General Science & Ability, Current Affairs, Pakistan Affairs, Islamic Studies] 600 marks + 6 Optional subjects 600 marks).';
            $lines[] = '   3. Medical Examination, Psychological Assessment, and Viva Voce (300 marks).';
            $lines[] = '- Emphasis: High-level analytical depth, structured arguments, international references, and critical thinking.';
        } elseif (str_contains($domainSlug, 'mdcat')) {
            $lines[] = '';
            $lines[] = 'INSTITUTIONAL DOMAIN KNOWLEDGE (MDCAT - PMDC):';
            $lines[] = '- 200 MCQs covering Biology, Chemistry, Physics, English, and Logical Reasoning based on the national PMDC syllabus.';
        }

        // Active candidate context (module & question)
        if (! empty($context['active_module_id'])) {
            $module = ContentModule::find($context['active_module_id']);
            if ($module instanceof ContentModule) {
                $lines[] = '';
                $lines[] = "CURRENT ACTIVE TEST MODULE: {$module->title} ({$module->module_type})";
            }
        }

        if (! empty($context['active_mcq']) && is_array($context['active_mcq'])) {
            $mcq = $context['active_mcq'];
            $lines[] = '';
            $lines[] = "CURRENT ACTIVE MCQ [ID #{$mcq['id']}]: {$mcq['statement']}";
            if (! empty($mcq['options']) && is_array($mcq['options'])) {
                $lines[] = 'Question Choices:';
                foreach ($mcq['options'] as $opt) {
                    $lines[] = "  {$opt}";
                }
            }
            if (! empty($mcq['user_selected'])) {
                $lines[] = "Candidate currently selected choice: {$mcq['user_selected']}";
            }
            if (isset($mcq['attempts_made'])) {
                $lines[] = "Candidate prior incorrect attempts on this question: {$mcq['attempts_made']}";
            }

            // Hidden backend evaluation ground truth (NEVER sent to frontend)
            $qRecord = QuestionBank::find($mcq['id']);
            if ($qRecord instanceof QuestionBank && isset($qRecord->payload['correct_option_index'])) {
                $cIdx = (int) $qRecord->payload['correct_option_index'];
                $letter = chr(65 + $cIdx);
                $optText = $qRecord->payload['options'][$cIdx] ?? '';
                $optStr = is_array($optText) ? ($optText['text'] ?? '') : (string) $optText;
                $lines[] = '';
                $lines[] = "[PRIVATE BACKEND GROUND TRUTH: The correct answer is Option {$letter} ({$optStr}). Use this solely to assess the candidate's answers and guide them Socratically. Under NO circumstances should you reveal this letter or option to the candidate.]";
            }
        } elseif (! empty($context['active_question_id'])) {
            $question = QuestionBank::find($context['active_question_id']);
            if ($question instanceof QuestionBank) {
                $promptText = strip_tags((string) ($question->question_text ?? ''));
                $lines[] = '';
                $lines[] = "CURRENT ACTIVE QUESTION [{$question->code}]: {$promptText}";
                if (! empty($question->payload['options'])) {
                    $lines[] = 'Question Choices:';
                    foreach ($question->payload['options'] as $idx => $opt) {
                        $letter = chr(65 + (int) $idx);
                        $optText = is_array($opt) ? ($opt['text'] ?? '') : (string) $opt;
                        $lines[] = "  {$letter}: {$optText}";
                    }
                }
            }
        } elseif (! empty($context['active_question_text'])) {
            $lines[] = '';
            $lines[] = "CANDIDATE VIEWING QUESTION: {$context['active_question_text']}";
        }

        // Injected verified pgvector knowledge chunks
        if ($knowledgeChunks->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'VERIFIED DOMAIN KNOWLEDGE (RETRIEVED FROM PGVECTOR):';
            foreach ($knowledgeChunks as $i => $chunk) {
                $num = $i + 1;
                $lines[] = "--- SOURCE [{$num}] ({$chunk->title} | Type: {$chunk->source_type}) ---";
                $lines[] = $chunk->content;
            }
        }

        return implode("\n", $lines);
    }
}
