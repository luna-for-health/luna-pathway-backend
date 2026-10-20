<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'luna' => [
        'base_url' => env('LUNA_API_URL'),
        'token' => env('LUNA_INTERNAL_API_TOKEN'),
        'timeout' => (int) env('LUNA_API_TIMEOUT', 10),
    ],

    'brevo' => [
        'api_key' => env('BREVO_API_KEY'),
        'from_address' => env('BREVO_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'from_name' => env('BREVO_FROM_NAME', env('MAIL_FROM_NAME', env('APP_NAME'))),
        'default_subject' => env('BREVO_DEFAULT_SUBJECT', env('APP_NAME')),
        'timeout' => (int) env('BREVO_TIMEOUT', 10),
        'templates' => [
            'consultation_request_created' => env('BREVO_TEMPLATE_CONSULTATION_REQUEST_CREATED'),
        ],
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
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_CLIENT_CALLBACK'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect' => env('APPLE_REDIRECT_URI'),
    ],

];
