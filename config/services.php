<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'chatbot_ai' => [
        // Set to fallback_only to test the dynamic local matcher without any AI requests.
        // Keep the default as ai for normal operation.
        'mode' => env('CHATBOT_AI_MODE', 'ai'),
        // Use Groq for FAQ matching after its credentials have been configured.
        'provider' => env('CHATBOT_AI_PROVIDER', 'groq'),
        // FAQ matching only returns a small JSON decision, but reasoning models
        // need room for internal reasoning plus that final decision.
        'retrieval_max_tokens' => (int) env('CHATBOT_AI_RETRIEVAL_MAX_TOKENS', 256),
    ],
    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_MODEL', 'openai/gpt-oss-20b'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
        'max_tokens' => (int) env('GROQ_MAX_TOKENS', 512),
    ],

    'openrouter' => [
    'api_key' => env('OPENROUTER_API_KEY'),

    // Primary router for the public FAQ retriever.
    // Keep the fallback chain on independent free providers so a single
    // provider's shared pool cannot take the chatbot down.
    'model' => env(
        'OPENROUTER_MODEL',
        'openrouter/free'
    ),
    'fallback_models' => env(
        'OPENROUTER_FALLBACK_MODELS',
        'nvidia/nemotron-3-ultra-550b-a55b:free,poolside/laguna-s-2.1:free,nvidia/nemotron-3.5-lightning:free'
    ),
    'retrieval_models' => array_values(array_filter(
        array_map(
            'trim',
            explode(',', env(
                'OPENROUTER_RETRIEVAL_MODELS',
                'openrouter/free,nvidia/nemotron-3-ultra-550b-a55b:free,poolside/laguna-s-2.1:free,nvidia/nemotron-3.5-lightning:free'
            ))
        ),
        static fn (string $value): bool => $value !== ''
    )),
    'cache_store' => env('OPENROUTER_CACHE_STORE', 'file'),
    'allow_provider_fallbacks' => filter_var(env('OPENROUTER_ALLOW_PROVIDER_FALLBACKS', 'false'), FILTER_VALIDATE_BOOLEAN),
    'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 512),
],

];
