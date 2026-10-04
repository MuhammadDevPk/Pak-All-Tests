<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class AiCompletionService
{
    protected string $driver;

    public function __construct(?string $driver = null)
    {
        $this->driver = $driver ?? Config::get('ai.chat_driver', 'fake');
    }

    /**
     * Switch chat driver fluently.
     */
    public function driver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Generate complete response (non-streaming).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function complete(array $messages): string
    {
        $fullText = '';
        $this->stream($messages, function (string $token) use (&$fullText) {
            $fullText .= $token;
        });

        return $fullText;
    }

    /**
     * Stream response tokens via callback in real time.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(string): void  $onToken
     */
    public function stream(array $messages, callable $onToken): string
    {
        return match ($this->driver) {
            'fake' => $this->streamFakeResponse($messages, $onToken),
            'openai' => $this->streamOpenAiResponse($messages, $onToken),
            'gemini' => $this->streamGeminiResponse($messages, $onToken),
            default => throw new InvalidArgumentException("Unsupported AI chat driver: [{$this->driver}]."),
        };
    }

    /**
     * Generate deterministic Socratic mentoring responses for testing/offline environments.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(string): void  $onToken
     */
    protected function streamFakeResponse(array $messages, callable $onToken): string
    {
        $lastMessage = end($messages)['content'] ?? '';
        $lastLower = strtolower($lastMessage);

        $systemPrompt = '';
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt = $msg['content'];
                break;
            }
        }

        // 1. Greetings
        if (preg_match('/\b(hi|hello|salam|aoa|hey|greetings)\b/i', $lastLower)) {
            $response = 'Hello Cadet! I am your dedicated AI Exam Mentor on Pak-All-Tests. Whether you need clarity on test structure, step-by-step problem breakdown, or time management strategy, I am here to guide you. What topic or problem would you like to master today?';
        }
        // 2. Initial Test & Exam overview
        elseif (str_contains($lastLower, 'initial') && (str_contains($lastLower, 'what') || str_contains($lastLower, 'tell') || str_contains($lastLower, 'mean') || str_contains($lastLower, 'is'))) {
            $response = "**PMA Long Course Initial Test Overview**:\n\nThe **Initial Test** is the preliminary screening phase conducted at Army Selection and Recruitment Centers (AS&RC). It includes:\n\n1. **Verbal Intelligence Test**: 84 questions in 30 minutes (analogies, number series, coding-decoding, classification).\n2. **Non-Verbal Intelligence Test**: 64 questions in 30 minutes (matrix reasoning, pattern completion, odd figure out).\n3. **Academic Test**: 50 questions in 30 minutes (English grammar, Mathematics, Pakistan Studies, Islamic Studies, and General Knowledge).\n4. **Physical Fitness Test**: 1.6 km run in 8 minutes, push-ups, chin-ups, sit-ups, and ditch crossing.\n5. **Initial Medical & Initial Interview**: Brief board interview before qualifying for ISSB call letters.\n\nWhich section would you like to practice first?";
        }
        // 3. WAT and SRT (Psychological tests)
        elseif (str_contains($lastLower, 'wt') || str_contains($lastLower, 'wat') || str_contains($lastLower, 'srt')) {
            $response = "**ISSB Psychological Tests: WAT & SRT**:\n\n1. **WAT (Word Association Test)**:\n   - **Format**: 60 English words are flashed on a screen for **10 seconds each**.\n   - **Task**: Write a spontaneous, meaningful, positive sentence for each word.\n   - **Evaluation**: Assesses your subconscious mindset, optimism, emotional stability, and officer-like qualities (OLQs).\n\n2. **SRT (Situation Reaction Test)**:\n   - **Format**: 10 real-life crisis/command scenarios given in a booklet.\n   - **Time**: 10 minutes total.\n   - **Task**: Write your immediate, realistic course of action under pressure.\n   - **Evaluation**: Tests practical common sense, courage, resourcefulness, and leadership under stress.";
        }
        // 4. Academics preparation
        elseif (str_contains($lastLower, 'academic')) {
            $response = "**How to Prepare for the PMA Academic Test**:\n\nThe Academic Test tests core intermediate-level fundamentals across 5 key subjects:\n\n1. **English (30%)**: Direct/Indirect speech, Active/Passive voice, Prepositions, Synonyms/Antonyms, and Sentence Correction.\n2. **Mathematics (25%)**: Basic Algebra, Quadratic Equations, Matrices, Trigonometry, and Percentages/Ratios.\n3. **Pakistan Studies (15%)**: Pre-partition history (1857–1947), Constitutional milestones, Geography, and National symbols.\n4. **Islamic Studies (15%)**: Battles of Islam (Ghazwat), Quranic revelations, Pillars of Islam, and Companions of the Prophet (PBUH).\n5. **General Knowledge & Current Affairs (15%)**: World capitals, currencies, international treaties, and current civil/military leadership.\n\n*Tip*: Bank the intelligence tests first with maximum speed so you enter the Academic test with high confidence!";
        }
        // 5. Sequence & Number series problems (e.g. 2, 4, 8, 16)
        elseif (str_contains($lastLower, 'sequence') || str_contains($lastLower, 'series') || (str_contains($lastLower, '2') && str_contains($lastLower, '4') && str_contains($lastLower, '8'))) {
            $response = "**Pattern Breakdown for Sequence (2, 4, 8, 16, ?)**:\n\n1. **Level 1 (Core Relationship)**: Compare each number to its predecessor: 4 = 2 × 2, 8 = 4 × 2, 16 = 8 × 2.\n2. **Level 2 (Rule Formulation)**: This is a geometric progression where each successive term is multiplied by a constant ratio of **2** (2¹, 2², 2³, 2⁴, 2⁵...).\n3. **Level 3 (Solution)**: Multiply the last term by 2: 16 × 2 = **32**.\n\nThe next number in the sequence is **32**.";
        }
        // 6. Socratic Hints
        elseif (str_contains($lastLower, 'hint') || str_contains($lastLower, 'clue')) {
            $response = '**Socratic Hint**: Focus on the functional or structural relationship between the components. Ask yourself: is this relationship about part-to-whole, cause-and-effect, or degree of intensity? Eliminate the options that reverse this hierarchy first.';
        }
        // 7. General Pattern
        elseif (str_contains($lastLower, 'pattern') || str_contains($lastLower, 'breakdown') || str_contains($lastLower, 'step')) {
            $response = "**Step-by-Step Analysis**:\n1. **Identify the Core Premise**: Separate the given terms into their primary attributes.\n2. **Test Relationships**: Check if the analogy follows spatial, sequential, or logical progression.\n3. **Eliminate Distractors**: Notice how two options might sound plausible but invert the necessary order.\n4. **Formulate Your Decision**: Which remaining option preserves the exact same rule?";
        }
        // 8. Passing / Criteria
        elseif (str_contains($lastLower, 'passing') || str_contains($lastLower, 'score') || str_contains($lastLower, 'criteria') || str_contains($lastLower, 'time')) {
            $response = '**Exam Strategy & Criteria**: For this examination, time discipline is paramount. Aim for an average of 20–25 seconds per question. Never get stuck on a single item—flag difficult questions, bank the quick marks first, and return with a fresh perspective.';
        }
        // 9. Active Question / Viewport Query (e.g., "Now I'm on which question?", "what content is on my page")
        elseif (preg_match('/\b(which question|what question|on my page|current question|what content)\b/i', $lastLower)) {
            if (str_contains($systemPrompt, 'CANDIDATE VIEWPORT / SCREEN STATE:')) {
                // Parse page & visible questions from system prompt if present
                if (preg_match('/Candidate is looking at Page (\d+) of (\d+), displaying Questions (\d+) to (\d+)/i', $systemPrompt, $pageMatches)) {
                    $page = $pageMatches[1];
                    $totalPages = $pageMatches[2];
                    $from = $pageMatches[3];
                    $to = $pageMatches[4];
                    $response = "You are currently viewing **Page {$page} of {$totalPages}** (Questions {$from} to {$to}) in Page View mode.";
                    if (preg_match('/Primary focused question on this page: Question (\d+)/i', $systemPrompt, $focusedMatch)) {
                        $response .= " Your active focus on this page is **Question {$focusedMatch[1]}**.";
                    }
                } elseif (preg_match('/Candidate is focused on Question (\d+)/i', $systemPrompt, $focusMatches)) {
                    $response = "You are currently in Focus Mode on **Question {$focusMatches[1]}**.";
                } else {
                    $response = 'You are currently active in your exam session with multiple questions on your screen.';
                }
            } elseif (preg_match('/PRIMARY FOCUSED MCQ Question #(\d+)/i', $systemPrompt, $mcqMatches)) {
                $response = "You are currently working on **Question {$mcqMatches[1]}**.";
            } else {
                $response = "You are currently in your active test module. Let me know which question you'd like guidance on!";
            }
        }
        // 10. Conversational default
        else {
            $response = "In competitive examinations, clarity and strategic discipline are key to scoring top percentiles.\n\nCould you specify whether you would like to practice **Intelligence Tests** (Verbal/Non-Verbal), review **Academic concepts**, or get a step-by-step hint on a specific question?";
        }

        // Stream tokens in natural word chunks
        $tokens = preg_split('/(\s+)/', $response, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$response];
        $fullText = '';

        foreach ($tokens as $token) {
            $onToken($token);
            $fullText .= $token;
            // Brief micro-pause for realistic streaming feel in web requests if not running in CLI tests
            if (! app()->runningUnitTests()) {
                usleep(15000); // 15ms
            }
        }

        return $fullText;
    }

    /**
     * Stream response from OpenAI's Chat Completions API.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(string): void  $onToken
     */
    protected function streamOpenAiResponse(array $messages, callable $onToken): string
    {
        $apiKey = Config::get('ai.openai.api_key');
        $model = Config::get('ai.openai.chat_model', 'gpt-4o-mini');
        $baseUrl = rtrim(Config::get('ai.openai.base_url', 'https://api.openai.com/v1'), '/');

        if (empty($apiKey)) {
            throw new RuntimeException('OpenAI API key is missing. Set OPENAI_API_KEY in your .env file.');
        }

        $fullText = '';

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->withOptions([
                'stream' => true,
            ])
            ->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => 800,
                'stream' => true,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException("OpenAI completion API error ({$response->status()}): {$response->body()}");
        }

        $body = $response->toPsrResponse()->getBody();
        $buffer = '';

        while (! $body->eof()) {
            $chunk = $body->read(256);
            $buffer .= $chunk;

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if (str_starts_with($line, 'data: ')) {
                    $jsonStr = substr($line, 6);
                    if ($jsonStr === '[DONE]') {
                        break 2;
                    }

                    $data = json_decode($jsonStr, true);
                    $delta = $data['choices'][0]['delta']['content'] ?? '';
                    if ($delta !== '') {
                        $onToken($delta);
                        $fullText .= $delta;
                    }
                }
            }
        }

        return $fullText;
    }

    /**
     * Stream response from Google Gemini API.
     * Sanitizes conversation history to strictly enforce Gemini's alternating role requirements.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(string): void  $onToken
     */
    protected function streamGeminiResponse(array $messages, callable $onToken): string
    {
        $apiKey = Config::get('ai.gemini.api_key');
        $model = Config::get('ai.gemini.chat_model', 'gemini-1.5-flash');
        $baseUrl = rtrim(Config::get('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        if (empty($apiKey)) {
            throw new RuntimeException('Gemini API key is missing. Set GEMINI_API_KEY in your .env file.');
        }

        $rawContents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = ['parts' => [['text' => $msg['content']]]];
            } else {
                $rawContents[] = [
                    'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => $msg['content']]],
                ];
            }
        }

        // Gemini enforces:
        // 1. First turn must be 'user' (drop leading 'model' turns if history was truncated)
        while (! empty($rawContents) && $rawContents[0]['role'] === 'model') {
            array_shift($rawContents);
        }

        // 2. Strict alternating turns (merge consecutive turns of the same role)
        $contents = [];
        foreach ($rawContents as $turn) {
            $lastIndex = count($contents) - 1;
            if ($lastIndex >= 0 && $contents[$lastIndex]['role'] === $turn['role']) {
                $contents[$lastIndex]['parts'][] = $turn['parts'][0];
            } else {
                $contents[] = $turn;
            }
        }

        if (empty($contents)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => 'Hello']],
            ];
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => 800,
                'temperature' => 0.4,
            ],
        ];
        if ($systemInstruction) {
            $payload['system_instruction'] = $systemInstruction;
        }

        $fullText = '';
        $response = Http::timeout(60)
            ->post("{$baseUrl}/models/{$model}:generateContent?key={$apiKey}", $payload);

        if (! $response->successful()) {
            throw new RuntimeException("Gemini completion API error: {$response->body()}");
        }

        $candidates = $response->json('candidates', []);
        $content = $candidates[0]['content']['parts'][0]['text'] ?? '';

        $tokens = preg_split('/(\s+)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$content];
        foreach ($tokens as $tok) {
            $onToken($tok);
            $fullText .= $tok;
        }

        return $fullText;
    }
}
