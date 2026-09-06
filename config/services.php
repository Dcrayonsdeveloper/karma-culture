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

    'anthropic' => [
        'key'   => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-haiku-4-5'),
    ],

    'gemini' => [
        'key'   => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
    ],

    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'api_secret' => env('GA4_API_SECRET'),
    ],

    'facebook' => [
        'pixel_id' => env('FB_PIXEL_ID'),
        'access_token' => env('FB_ACCESS_TOKEN'),
        'test_event_code' => env('FB_TEST_EVENT_CODE'),
    ],

    // The tracking webhook can move an order to delivered/cancelled, so this
    // token is required - an unset value rejects every request rather than
    // leaving the endpoint open.
    'shiprocket' => [
        'webhook_token' => env('SHIPROCKET_WEBHOOK_TOKEN'),
    ],

    'meta' => [
        'page_access_token'        => env('META_PAGE_ACCESS_TOKEN'),
        'app_secret'               => env('META_APP_SECRET'),
        'verify_token'             => env('META_VERIFY_TOKEN'),
        'whatsapp_phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
    ],

    /*
     * The feed is read through the Facebook Graph API, not graph.instagram.com.
     * Basic Display is the personal-account API and it cannot parse the
     * system-user token this business holds - it answers "Cannot parse access
     * token" for a token that is perfectly valid, which is why the strip has
     * been empty. A Business account is addressed by its own numeric id, found
     * once with:
     *
     *   GET /v21.0/{page-id}?fields=instagram_business_account
     *
     * Both values live in the environment so the account can be changed without
     * a deploy, and so no account id ships in this repository.
     */
    'instagram' => [
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
        'user_id' => env('INSTAGRAM_USER_ID'),
        'graph_version' => env('INSTAGRAM_GRAPH_VERSION', 'v21.0'),
    ],

];
