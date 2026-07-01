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
