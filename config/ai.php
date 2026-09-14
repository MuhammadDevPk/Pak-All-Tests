<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Embedding Driver
    |--------------------------------------------------------------------------
    |
    | Supported drivers: "fake" (deterministic for offline/testing),
    | "openai" (text-embedding-3-small), "gemini" (text-embedding-004).
    |
    */

    'embedding_driver' => env('AI_EMBEDDING_DRIVER', env('OPENAI_API_KEY') ? 'openai' : 'fake'),

    'chat_driver' => env('AI_CHAT_DRIVER', env('OPENAI_API_KEY') ? 'openai' : 'fake'),

    'embedding_dimension' => 1536,

    'auto_vectorize' => env('AI_AUTO_VECTORIZE', true),

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'),
        'chat_model' => env('GEMINI_CHAT_MODEL', 'gemini-1.5-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

];
