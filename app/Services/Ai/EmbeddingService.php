<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class EmbeddingService
{
    protected string $driver;

    protected int $dimension;

    public function __construct(?string $driver = null)
    {
        $this->driver = $driver ?? Config::get('ai.embedding_driver', 'fake');
        $this->dimension = (int) Config::get('ai.embedding_dimension', 1536);
    }

    /**
     * Switch driver fluently.
     */
    public function driver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Returns the target embedding vector dimension.
     */
    public function dimension(): int
    {
        return $this->dimension;
    }

    /**
     * Generate an embedding vector for a single text chunk.
     *
     * @return array<int, float>
     */
    public function embed(string $text): array
    {
        $batch = $this->embedBatch([$text]);

        return $batch[0] ?? [];
    }

    /**
     * Generate embedding vectors for a batch of text chunks.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    public function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        return match ($this->driver) {
            'fake' => $this->generateFakeEmbeddings($texts),
            'openai' => $this->generateOpenAiEmbeddings($texts),
            'gemini' => $this->generateGeminiEmbeddings($texts),
            default => throw new InvalidArgumentException("Unsupported AI embedding driver: [{$this->driver}]."),
        };
    }

    /**
     * Format a float vector array as a PostgreSQL pgvector literal string.
     *
     * @param  array<int, float>  $vector
     */
    public static function vectorToSql(array $vector): string
    {
        return '['.implode(',', array_map(fn ($val) => number_format((float) $val, 8, '.', ''), $vector)).']';
    }

    /**
     * Deterministic, word-sensitive normalized vector generator for offline/testing use.
     * Generates a unit-length 1536-dimensional vector where overlapping terms share vector components.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    protected function generateFakeEmbeddings(array $texts): array
    {
        $results = [];

        foreach ($texts as $text) {
            $vector = array_fill(0, $this->dimension, 0.0);
            $words = preg_split('/[^a-zA-Z0-9]+/', strtolower(trim($text)), -1, PREG_SPLIT_NO_EMPTY) ?: ['empty'];

            foreach ($words as $word) {
                // Generate 3 deterministic dimensional indices per word to simulate distributed semantics
                for ($i = 0; $i < 3; $i++) {
                    $hash = crc32($word.':'.$i);
                    $index = abs($hash) % $this->dimension;
                    $weight = (($hash % 1000) / 1000.0);
                    $vector[$index] += $weight;
                }
            }

            // Normalize to unit length (L2 norm)
            $norm = sqrt(array_sum(array_map(fn ($val) => $val * $val, $vector)));
            if ($norm > 0) {
                $vector = array_map(fn ($val) => round($val / $norm, 6), $vector);
            } else {
                $vector[0] = 1.0;
            }

            $results[] = $vector;
        }

        return $results;
    }

    /**
     * Generate embeddings using OpenAI's API.
     * Chunks input in batches of up to 500 to prevent request payload overflows.
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    protected function generateOpenAiEmbeddings(array $texts): array
    {
        $apiKey = Config::get('ai.openai.api_key');
        $model = Config::get('ai.openai.model', 'text-embedding-3-small');
        $baseUrl = rtrim(Config::get('ai.openai.base_url', 'https://api.openai.com/v1'), '/');

        if (empty($apiKey)) {
            throw new RuntimeException('OpenAI API key is missing. Set OPENAI_API_KEY in your .env file.');
        }

        // Sanitize empty strings to avoid OpenAI 400 errors
        $sanitized = array_map(fn ($t) => trim((string) $t) === '' ? ' ' : $t, $texts);
        $chunks = array_chunk($sanitized, 500);
        $allEmbeddings = [];

        foreach ($chunks as $chunk) {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->retry(2, 500)
                ->post("{$baseUrl}/embeddings", [
                    'input' => array_values($chunk),
                    'model' => $model,
                    'dimensions' => $this->dimension,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException("OpenAI embeddings API error ({$response->status()}): {$response->body()}");
            }

            $data = $response->json('data', []);
            usort($data, fn ($a, $b) => ($a['index'] ?? 0) <=> ($b['index'] ?? 0));

            foreach ($data as $item) {
                $allEmbeddings[] = $item['embedding'];
            }
        }

        return $allEmbeddings;
    }

    /**
     * Generate embeddings using Google Gemini's API.
     * Chunks requests into batches of at most 100 (Gemini batchEmbedContents limit).
     *
     * @param  array<int, string>  $texts
     * @return array<int, array<int, float>>
     */
    protected function generateGeminiEmbeddings(array $texts): array
    {
        $apiKey = Config::get('ai.gemini.api_key');
        $model = Config::get('ai.gemini.model', 'text-embedding-004');
        $baseUrl = rtrim(Config::get('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');

        if (empty($apiKey)) {
            throw new RuntimeException('Gemini API key is missing. Set GEMINI_API_KEY in your .env file.');
        }

        $sanitized = array_map(fn ($t) => trim((string) $t) === '' ? ' ' : $t, $texts);
        // Gemini batchEmbedContents accepts at most 100 requests per batch
        $chunks = array_chunk($sanitized, 100);
        $allEmbeddings = [];

        foreach ($chunks as $chunk) {
            $requests = array_map(fn ($text) => [
                'model' => "models/{$model}",
                'content' => ['parts' => [['text' => $text]]],
                'outputDimensionality' => $this->dimension,
            ], $chunk);

            $response = Http::timeout(30)
                ->retry(2, 500)
                ->post("{$baseUrl}/models/{$model}:batchEmbedContents?key={$apiKey}", [
                    'requests' => $requests,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException("Gemini embeddings API error ({$response->status()}): {$response->body()}");
            }

            $embeddings = $response->json('embeddings', []);
            foreach ($embeddings as $item) {
                $allEmbeddings[] = $item['values'] ?? [];
            }
        }

        return $allEmbeddings;
    }
}
