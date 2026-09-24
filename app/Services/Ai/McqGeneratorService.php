<?php

namespace App\Services\Ai;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use ZipArchive;

class McqGeneratorService
{
    protected string $driver;

    public function __construct(
        protected ?AiCompletionService $completionService = null,
        ?string $driver = null
    ) {
        $this->driver = $driver ?? Config::get('ai.chat_driver', 'fake');
    }

    /**
     * Switch AI driver fluently.
     */
    public function driver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Parse complete MCQs from raw text (0 Tokens, Pure PHP).
     *
     * Handles multiline, inline options (a) b) c) d) on one line),
     * various option delimiters (A), (A), [A], A., A:), and answer keys.
     *
     * @return array{questions: array<int, array{question_text: string, options: array<int, string>, correct_option_index: int, explanation: ?string, difficulty_level: string, code: ?string}>, unparsed_lines: array<int, string>}
     */
    public function parseCompleteMcqs(string $rawText): array
    {
        $normalizedText = $this->normalizeRawText($rawText);
        $lines = preg_split('/\r\n|\r|\n/', $normalizedText) ?: [];

        $questions = [];
        $unparsedLines = [];

        $currentQuestion = null;
        /** @var array<string, string> $currentOptions */
        $currentOptions = [];
        $currentAnswerKey = null;
        $currentExplanation = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            // 1. Detect Answer line (e.g. "Answer: C", "Ans: B", "Correct: A", "Answer: Option 2")
            if (preg_match('/^(?:ans(?:wer)?|correct(?:\s*answer)?|key)\s*[:=\-]?\s*(?:option\s*)?([a-eA-E1-5]|\S+.*)$/i', $trimmed, $m)) {
                $currentAnswerKey = trim($m[1]);

                continue;
            }

            // 2. Detect Explanation line (e.g. "Explanation: ...", "Solution: ...")
            if (preg_match('/^(?:explanation|solution|rationale|note)\s*[:=\-]\s*(.*)$/i', $trimmed, $m)) {
                $currentExplanation = trim($m[1]);

                continue;
            }

            // 3. Detect Option lines (e.g. "A) Lahore", "(A) Lahore", "A. Lahore", "[A] Lahore")
            // Strictly matches letters a-e so numbers like "1. Question" are not misidentified
            if (preg_match('/^(?:(?:\(?([a-eA-E])[\)\.\:\]])|\[([a-eA-E])\])\s+(.+)$/i', $trimmed, $m)) {
                $optLetter = strtoupper($m[1] ?: $m[2]);
                $optText = trim($m[3]);
                $currentOptions[$optLetter] = $optText;

                continue;
            }

            // 4. Detect New Question Prompt (e.g. "1. What is...", "Q1: What is...", "Q. What is...")
            if (preg_match('/^(?:q(?:uestion)?[\s\.\:\-]*\d*|\d+[\.\)\:\-])\s*(.+)$/i', $trimmed, $m)) {
                $this->finalizeParsedQuestion(
                    $questions,
                    $unparsedLines,
                    $currentQuestion,
                    $currentOptions,
                    $currentAnswerKey,
                    $currentExplanation
                );
                $currentQuestion = trim($m[1]);

                continue;
            }

            // 5. Continuation of question prompt or unparsed content
            if ($currentQuestion && empty($currentOptions)) {
                $currentQuestion .= ' '.$trimmed;
            } elseif ($currentExplanation !== null) {
                $currentExplanation .= ' '.$trimmed;
            } elseif ($currentQuestion === null && empty($currentOptions)) {
                // Standalone unnumbered question line
                $this->finalizeParsedQuestion(
                    $questions,
                    $unparsedLines,
                    $currentQuestion,
                    $currentOptions,
                    $currentAnswerKey,
                    $currentExplanation
                );
                $currentQuestion = $trimmed;
            } else {
                $unparsedLines[] = $trimmed;
            }
        }

        $this->finalizeParsedQuestion(
            $questions,
            $unparsedLines,
            $currentQuestion,
            $currentOptions,
            $currentAnswerKey,
            $currentExplanation
        );

        return [
            'questions' => $questions,
            'unparsed_lines' => $unparsedLines,
        ];
    }

    /**
     * Finalize and store parsed question candidate.
     *
     * @param  array<int, array{question_text: string, options: array<int, string>, correct_option_index: int, explanation: ?string, difficulty_level: string, code: ?string}>  $questions
     * @param  array<int, string>  $unparsedLines
     * @param  array<string, string>  $currentOptions
     */
    protected function finalizeParsedQuestion(
        array &$questions,
        array &$unparsedLines,
        ?string &$currentQuestion,
        array &$currentOptions,
        ?string &$currentAnswerKey,
        ?string &$currentExplanation
    ): void {
        if ($currentQuestion && count($currentOptions) >= 2) {
            $correctIndex = $this->resolveCorrectOptionIndex($currentOptions, $currentAnswerKey);

            $questions[] = [
                'question_text' => trim($currentQuestion),
                'options' => array_values($currentOptions),
                'correct_option_index' => $correctIndex,
                'explanation' => $currentExplanation ? trim($currentExplanation) : null,
                'difficulty_level' => 'medium',
                'code' => null,
            ];
        } elseif ($currentQuestion && empty($currentOptions)) {
            $unparsedLines[] = trim($currentQuestion);
        }

        $currentQuestion = null;
        $currentOptions = [];
        $currentAnswerKey = null;
        $currentExplanation = null;
    }

    /**
     * Parse raw text into structured Q&A pairs.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function parseQaPairs(string $rawText): array
    {
        $qaPairs = [];
        $lines = preg_split('/\r\n|\r|\n/', $rawText) ?: [];
        $currQ = null;

        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') {
                continue;
            }

            if (preg_match('/^(?:ans(?:wer)?|a)[:=\-\s]+(.*)$/i', $t, $m) && $currQ !== null) {
                $qaPairs[] = ['question' => $currQ, 'answer' => trim($m[1])];
                $currQ = null;
            } elseif (preg_match('/^(?:q(?:uestion)?|\d+)[:=\.\-\s]+(.*)$/i', $t, $m)) {
                $currQ = trim($m[1]);
            } elseif ($currQ !== null) {
                $qaPairs[] = ['question' => $currQ, 'answer' => $t];
                $currQ = null;
            } else {
                $currQ = $t;
            }
        }

        return $qaPairs;
    }

    /**
     * Generate 3 realistic distractors and an explanation for bare Q&A pairs via AI.
     *
     * Processes in micro-batches of 10 items to prevent token truncation.
     *
     * @param  array<int, array{question: string, answer: string}>  $qaPairs
     * @return array<int, array{question_text: string, options: array<int, string>, correct_option_index: int, explanation: string, difficulty_level: string, code: ?string}>
     */
    public function generateDistractorsForPairs(array $qaPairs, string $difficulty = 'medium'): array
    {
        if (empty($qaPairs)) {
            return [];
        }

        if ($this->driver === 'fake') {
            return $this->generateFakeDistractors($qaPairs, $difficulty);
        }

        $chunks = array_chunk($qaPairs, 10);
        $results = [];

        foreach ($chunks as $chunk) {
            $prompt = $this->buildDistractorPrompt($chunk, $difficulty);
            $response = $this->callLlmJson($prompt);

            if (isset($response['questions']) && is_array($response['questions'])) {
                foreach ($response['questions'] as $q) {
                    $results[] = $this->sanitizeGeneratedQuestion($q, $difficulty);
                }
            }
        }

        return $results;
    }

    /**
     * Generate complete MCQs from syllabus notes or topic prompt from scratch via AI.
     *
     * @return array<int, array{question_text: string, options: array<int, string>, correct_option_index: int, explanation: string, difficulty_level: string, code: ?string}>
     */
    public function generateFromTopicOrText(string $promptOrDoc, int $count = 10, string $difficulty = 'medium'): array
    {
        if ($this->driver === 'fake') {
            return $this->generateFakeTopicQuestions($promptOrDoc, $count, $difficulty);
        }

        $batches = ceil($count / 10);
        $results = [];
        $remaining = $count;

        for ($b = 0; $b < $batches; $b++) {
            $batchSize = min(10, $remaining);
            $prompt = $this->buildTopicGenerationPrompt($promptOrDoc, $batchSize, $difficulty);
            $response = $this->callLlmJson($prompt);

            if (isset($response['questions']) && is_array($response['questions'])) {
                foreach ($response['questions'] as $q) {
                    $results[] = $this->sanitizeGeneratedQuestion($q, $difficulty);
                }
            }
            $remaining -= $batchSize;
        }

        return $results;
    }

    /**
     * Extract clean plain text from uploaded files (.txt, .csv, .docx).
     */
    public function readDocumentText(UploadedFile $file): string
    {
        return $this->extractTextFromFilePath($file->getRealPath());
    }

    /**
     * Extract plain text directly from a file path on disk (.txt, .csv, .docx).
     */
    public function extractTextFromFilePath(string $filePath): string
    {
        if (! file_exists($filePath)) {
            return '';
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'docx' => $this->extractTextFromDocx($filePath),
            default => (string) (file_get_contents($filePath) ?: ''),
        };
    }

    /**
     * Extract text from Microsoft Word .docx without external dependencies.
     */
    protected function extractTextFromDocx(string $filePath): string
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (! $xml) {
            return '';
        }

        // Replace paragraph and break tags with newlines
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $xml = preg_replace('/<w:br\/>/', "\n", $xml);

        return trim(strip_tags($xml));
    }

    /**
     * Normalize space-separated inline options (e.g. "a) Lahore b) Karachi c) Islamabad d) Peshawar").
     */
    protected function normalizeRawText(string $text): string
    {
        // Add newlines before inline options like " b) " or " B. " or " (B) "
        $text = preg_replace('/(?<!\n)\s+([a-eA-E][\)\.\]]|\([a-eA-E]\))\s+/i', "\n$1 ", $text);

        // Add newlines before inline answers like " Ans: A" or " Answer: B"
        return preg_replace('/(?<!\n)\s+(ans(?:wer)?[:=\-\s]+[a-eA-E1-5])/i', "\n$1", $text);
    }

    /**
     * Resolve 0-based integer index of correct option.
     *
     * @param  array<string, string>  $options
     */
    protected function resolveCorrectOptionIndex(array $options, ?string $answerKey): int
    {
        if ($answerKey === null) {
            return 0;
        }

        $keyUpper = strtoupper(trim($answerKey));
        $keys = array_keys($options);

        // 1. Direct letter match (A, B, C, D)
        $letterIndex = array_search($keyUpper, $keys, true);
        if ($letterIndex !== false) {
            return (int) $letterIndex;
        }

        // 2. Direct 1-based index (1, 2, 3, 4)
        if (is_numeric($answerKey)) {
            $num = (int) $answerKey - 1;
            if ($num >= 0 && $num < count($options)) {
                return $num;
            }
        }

        // 3. Match against option text
        $lowerKey = strtolower(trim($answerKey));
        $idx = 0;
        foreach ($options as $optText) {
            if (strtolower(trim($optText)) === $lowerKey) {
                return $idx;
            }
            $idx++;
        }

        return 0;
    }

    /**
     * Build prompt for distractor generation.
     *
     * @param  array<int, array{question: string, answer: string}>  $chunk
     */
    protected function buildDistractorPrompt(array $chunk, string $difficulty): string
    {
        $pairsText = '';
        foreach ($chunk as $i => $item) {
            $pairsText .= 'Question '.($i + 1).": {$item['question']}\nCorrect Answer: {$item['answer']}\n\n";
        }

        return <<<PROMPT
You are an expert exam question author for high-stakes Pakistani competitive exams (PMA Long Course, ISSB, CSS, PPSC, MDCAT).

Given these questions and their verified correct answers, generate 3 highly plausible, realistic distractors (wrong options) for each question.
- Format each question with exactly 4 options.
- Randomly shuffle the options so the correct answer is NOT always in the same position.
- Set "correct_option_index" to the 0-based integer index (0, 1, 2, or 3) of the correct answer.
- Provide a concise 1-2 sentence "explanation" for why the answer is correct.
- Set difficulty_level to "{$difficulty}".

Questions:
{$pairsText}

Return ONLY valid JSON matching this schema:
{
  "questions": [
    {
      "question_text": "...",
      "options": ["Choice A", "Choice B", "Choice C", "Choice D"],
      "correct_option_index": 0,
      "explanation": "...",
      "difficulty_level": "{$difficulty}"
    }
  ]
}
PROMPT;
    }

    /**
     * Build prompt for topic generation.
     */
    protected function buildTopicGenerationPrompt(string $topicOrText, int $count, string $difficulty): string
    {
        return <<<PROMPT
You are an expert exam author for Pakistani competitive exams (PMA, CSS, PPSC, MDCAT).
Draft exactly {$count} challenging Multiple Choice Questions (MCQs) based on the following topic or source material:

{$topicOrText}

Requirements:
- Target difficulty: {$difficulty}.
- Exactly 4 realistic options per question.
- "correct_option_index" as a 0-based integer (0-3).
- Authoritative 1-2 sentence solution explanation.

Return ONLY valid JSON matching this schema:
{
  "questions": [
    {
      "question_text": "...",
      "options": ["Option 1", "Option 2", "Option 3", "Option 4"],
      "correct_option_index": 0,
      "explanation": "...",
      "difficulty_level": "{$difficulty}"
    }
  ]
}
PROMPT;
    }

    /**
     * Call the configured LLM driver and return decoded JSON.
     *
     * @return array<string, mixed>
     */
    protected function callLlmJson(string $prompt): array
    {
        if ($this->driver === 'openai') {
            return $this->callOpenAiJson($prompt);
        }

        if ($this->driver === 'gemini') {
            return $this->callGeminiJson($prompt);
        }

        throw new RuntimeException("Unsupported AI driver: [{$this->driver}].");
    }

    /**
     * @return array<string, mixed>
     */
    protected function callOpenAiJson(string $prompt): array
    {
        $apiKey = Config::get('ai.openai.api_key');
        $model = Config::get('ai.openai.chat_model', 'gpt-4o-mini');
        $baseUrl = rtrim(Config::get('ai.openai.base_url', 'https://api.openai.com/v1'), '/');

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an exam author. Output strictly valid JSON.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.3,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('OpenAI API call failed: '.$response->body());
        }

        $content = $response->json('choices.0.message.content');

        return json_decode($content, true) ?: [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function callGeminiJson(string $prompt): array
    {
        $apiKey = Config::get('ai.gemini.api_key');
        $model = Config::get('ai.gemini.chat_model', 'gemini-1.5-flash');
        $baseUrl = rtrim(Config::get('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        $response = Http::timeout(60)->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.3,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gemini API call failed: '.$response->body());
        }

        $content = $response->json('candidates.0.content.parts.0.text');

        return json_decode($content, true) ?: [];
    }

    /**
     * Sanitize and validate generated question shape.
     *
     * @param  array<string, mixed>  $q
     * @return array{question_text: string, options: array<int, string>, correct_option_index: int, explanation: string, difficulty_level: string, code: ?string}
     */
    protected function sanitizeGeneratedQuestion(array $q, string $defaultDifficulty): array
    {
        $options = isset($q['options']) && is_array($q['options']) ? array_values($q['options']) : [];
        while (count($options) < 4) {
            $options[] = 'None of the above';
        }
        $options = array_slice($options, 0, 4);

        $correctIndex = isset($q['correct_option_index']) ? (int) $q['correct_option_index'] : 0;
        if ($correctIndex < 0 || $correctIndex >= count($options)) {
            $correctIndex = 0;
        }

        return [
            'question_text' => trim((string) ($q['question_text'] ?? 'Untitled Question')),
            'options' => $options,
            'correct_option_index' => $correctIndex,
            'explanation' => trim((string) ($q['explanation'] ?? '')),
            'difficulty_level' => in_array($q['difficulty_level'] ?? '', ['easy', 'medium', 'hard'], true)
                ? $q['difficulty_level']
                : $defaultDifficulty,
            'code' => null,
        ];
    }

    /**
     * Deterministic mock generator for tests / offline environments.
     *
     * @param  array<int, array{question: string, answer: string}>  $qaPairs
     * @return array<int, array{question_text: string, options: array<int, string>, correct_option_index: int, explanation: string, difficulty_level: string, code: ?string}>
     */
    protected function generateFakeDistractors(array $qaPairs, string $difficulty): array
    {
        $results = [];
        foreach ($qaPairs as $i => $item) {
            $correct = trim($item['answer']);
            $options = [
                $correct,
                $correct.' (Alternative)',
                'Incorrect Distractor A',
                'Incorrect Distractor B',
            ];

            $results[] = [
                'question_text' => trim($item['question']),
                'options' => $options,
                'correct_option_index' => 0,
                'explanation' => "Verified answer: {$correct}. Essential for competitive examination standards.",
                'difficulty_level' => $difficulty,
                'code' => null,
            ];
        }

        return $results;
    }

    /**
     * Deterministic mock generator for topic questions in tests / offline.
     *
     * @return array<int, array{question_text: string, options: array<int, string>, correct_option_index: int, explanation: string, difficulty_level: string, code: ?string}>
     */
    protected function generateFakeTopicQuestions(string $topic, int $count, string $difficulty): array
    {
        $results = [];
        for ($i = 1; $i <= $count; $i++) {
            $results[] = [
                'question_text' => "Sample Question {$i} on {$topic}?",
                'options' => [
                    "Correct Option for {$i}",
                    "Plausible Distractor 1 for {$i}",
                    "Plausible Distractor 2 for {$i}",
                    "Plausible Distractor 3 for {$i}",
                ],
                'correct_option_index' => 0,
                'explanation' => "Comprehensive explanation for Question {$i} on {$topic}.",
                'difficulty_level' => $difficulty,
                'code' => null,
            ];
        }

        return $results;
    }
}
