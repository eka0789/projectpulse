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

    'ai' => [
        'provider' => env('AI_PROVIDER', 'openai'),
        'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 20),
        'demo_fallback_enabled' => (bool) env('AI_DEMO_FALLBACK_ENABLED', false),
        'openai' => [
            'api_key' => env('OPENAI_API_KEY', env('AI_API_KEY')),
            'model' => env('OPENAI_MODEL', env('AI_MODEL', 'gpt-4o-mini')),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY', env('AI_API_KEY')),
            'model' => env('GEMINI_MODEL', env('AI_MODEL', 'gemini-2.0-flash')),
        ],
    ],

];
