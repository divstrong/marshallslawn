<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Twilio credentials
    |--------------------------------------------------------------------------
    |
    | Set the matching env vars in .env. Leave empty to disable SMS sending —
    | the TwilioService short-circuits and logs instead of throwing.
    |
    */

    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),

    // Either a from-number (E.164, e.g. +18045551234) OR a Messaging Service SID
    // (preferred for production; rotates numbers automatically).
    'from_number' => env('TWILIO_FROM_NUMBER'),
    'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),

    /*
    |--------------------------------------------------------------------------
    | Feature toggles
    |--------------------------------------------------------------------------
    |
    | Master switch for outbound customer SMS. Individual message types are
    | toggled per-template in Settings → Notifications (the sms_templates table),
    | so the office controls copy and on/off without a deploy. This flag is the
    | hard kill-switch for the whole channel.
    |
    */

    'notifications' => [
        'enabled' => env('TWILIO_NOTIFICATIONS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Double opt-in
    |--------------------------------------------------------------------------
    |
    | Required for A2P / CTIA-compliant consent. When enabled, a customer with a
    | phone number is sent a one-time opt-in request; no job/invoice notifications
    | go out until they reply YES. The inbound webhook records that confirmation
    | (and STOP opt-outs).
    |
    */

    'opt_in' => [
        'enabled' => env('TWILIO_DOUBLE_OPT_IN', false),
    ],
];
