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

    // Credentials, never settings. Every one of these used to be typed into
    // the admin panel and kept in the `settings` table, which put live payment
    // and courier secrets in reach of anyone with an admin login and in every
    // database dump. They are read from the environment only now, and
    // {@see \App\Models\Setting::ENV_ONLY_KEYS} refuses to store them again.
    'payu' => [
        'key'  => env('PAYU_MERCHANT_KEY'),
        'salt' => env('PAYU_MERCHANT_SALT'),
        // Which PayU endpoint the checkout posts to. It belongs beside the
        // key and salt rather than in the admin panel: the credentials are
        // per-mode, so flipping this on its own only ever breaks payments.
        'mode' => env('PAYU_MODE', 'test'),
    ],

    // The tracking webhook can move an order to delivered/cancelled, so this
    // token is required - an unset value rejects every request rather than
    // leaving the endpoint open.
    'shiprocket' => [
        'webhook_token' => env('SHIPROCKET_WEBHOOK_TOKEN'),
        // Either an API token on its own, or the email/password pair the
        // service logs in with when no token is set.
        'api_token' => env('SHIPROCKET_API_TOKEN'),
        'email'     => env('SHIPROCKET_EMAIL'),
        'password'  => env('SHIPROCKET_PASSWORD'),
    ],

    // Public identifiers rather than secrets - they are published in the page
    // source - but they steer third-party tracking, so they are pinned to the
    // deployment rather than left editable from a browser.
    'gtm' => [
        'id' => env('GOOGLE_TAG_MANAGER_ID'),
    ],

    'google' => [
        'site_verification' => env('GOOGLE_SITE_VERIFICATION'),
    ],

    'meta' => [
        'page_access_token'        => env('META_PAGE_ACCESS_TOKEN'),
        'app_secret'               => env('META_APP_SECRET'),
        'verify_token'             => env('META_VERIFY_TOKEN'),
        'whatsapp_phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID'),
    ],

    'instagram' => [
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN'),
    ],

];
