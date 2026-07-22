<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App Store review account
    |--------------------------------------------------------------------------
    |
    | Sign-in is passwordless, which App Review cannot complete on its own —
    | reviewers have no access to the mailbox a one-time code is sent to. When
    | both values below are set, this address skips the emailed code and accepts
    | the fixed code instead, and its employee record is provisioned on demand
    | so nothing has to be created by hand before a submission.
    |
    | Both default to empty, which disables the bypass. That default is
    | deliberate: the code is a fixed, publicly documented string, so an
    | enabled bypass is a credential anyone can use. Turn it on with env vars
    | for the review window and turn it back off once the build is approved.
    |
    |   APP_REVIEW_EMAIL=test@apple.com
    |   APP_REVIEW_CODE=123456
    |
    */

    'email' => env('APP_REVIEW_EMAIL', ''),

    'code' => env('APP_REVIEW_CODE', ''),

    /*
    | Role the provisioned account is given. Foreman is the role that has
    | background location tracking, which App Review needs to be able to reach.
    */
    'role' => env('APP_REVIEW_ROLE', 'foreman'),

    /*
    | Crew whose jobs the demo account can see. Jobs and Schedule are scoped by
    | crew, so without a crew the reviewer opens an app with empty lists. Leave
    | this unset to attach to the busiest upcoming crew automatically.
    */
    'crew_id' => env('APP_REVIEW_CREW_ID'),

];
