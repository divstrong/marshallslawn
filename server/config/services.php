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

    'google' => [
        'maps_key' => env('GOOGLE_MAPS_API_KEY'),
        // Google Cloud Translation API key (optional alternative translation driver).
        'translate_key' => env('GOOGLE_TRANSLATE_KEY'),
    ],

    /*
    | On-the-fly translation of admin-authored content (job details + chat) for
    | field staff whose app is set to another language (issue #56).
    |
    | driver:  'openai' (uses the key already in .env), 'google', or 'null'
    |          (a no-op passthrough for local dev / when translation is off).
    | Every unique source string is translated once and cached in the
    | `translations` table, so ongoing cost is near zero and repeat requests are
    | instant. A failed/absent provider always falls back to the original text —
    | translation never breaks an API response.
    */
    'translation' => [
        // Defaults to OpenAI (uses OPENAI_KEY). Falls back to English automatically
        // when no key is set, so this is safe even before the key is configured.
        'driver' => env('TRANSLATION_DRIVER', 'openai'),
        'openai' => [
            'key' => env('OPENAI_KEY'),
            'model' => env('OPENAI_TRANSLATION_MODEL', 'gpt-4o-mini'),
        ],
    ],

    /*
    | Accept Blue (via the Reliable Payments broker) — invoice card/ACH charging.
    | `mode` gates which endpoint is used so test charges never hit production:
    | in 'sandbox' mode the SANDBOX_ENDPOINT must be set explicitly, otherwise a
    | charge is refused rather than falling back to the live endpoint.
    */
    'accept_blue' => [
        'mode' => env('ACCEPT_BLUE_MODE', 'sandbox'), // 'sandbox' | 'live'
        'endpoint' => env('ACCEPT_BLUE_ENDPOINT'),
        'sandbox_endpoint' => env('ACCEPT_BLUE_SANDBOX_ENDPOINT'),
        'api_key' => env('ACCEPT_BLUE_API_KEY'),
        'source_key' => env('ACCEPT_BLUE_SOURCE_KEY'), // "pin"/source key = Basic auth password
        'auth_token' => env('ACCEPT_BLUE_AUTH_TOKEN'),  // pre-encoded Basic token (optional shortcut)
        // Public tokenization key + script for the hosted card fields on the pay page.
        'tokenization_key' => env('ACCEPT_BLUE_TOKENIZATION_KEY'),
        'tokenization_src' => env('ACCEPT_BLUE_TOKENIZATION_SRC'),
    ],

];
