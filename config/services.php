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

    'openrouter' => [
    'api_key' => env('OPENROUTER_API_KEY'),

    // Primary router for the public FAQ retriever.
    // Keep the fallback chain on independent free providers so a single
    // provider's shared pool cannot take the chatbot down.
    'model' => env(
        'OPENROUTER_MODEL',
        'qwen/qwen3.8-27b:free'
    ),
    'fallback_models' => env(
        'OPENROUTER_FALLBACK_MODELS',
        'inclusionai/ling-3.0-flash-fin:free,poolside/laguna-s-2.1:free,nvidia/nemotron-3.5-lightning:free'
    ),
    'retrieval_models' => array_values(array_filter(
        array_map(
            'trim',
            explode(',', env(
                'OPENROUTER_RETRIEVAL_MODELS',
                'qwen/qwen3.8-27b:free,inclusionai/ling-3.0-flash-fin:free,poolside/laguna-s-2.1:free,nvidia/nemotron-3.5-lightning:free'
            ))
        ),
        static fn (string $value): bool => $value !== ''
    )),
    'cache_store' => env('OPENROUTER_CACHE_STORE', 'file'),
    'max_tokens' => (int) env('OPENROUTER_MAX_TOKENS', 192),
],

];
