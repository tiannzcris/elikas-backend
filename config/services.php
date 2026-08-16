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
    /*'semaphore' => [
    'api_key' => env('SEMAPHORE_API_KEY'),
    'sender_name' => env('SEMAPHORE_SENDER_NAME', 'ELIKAS'),
    ],*/

    // sarima-service/ -- a separate Python/FastAPI process for SARIMA/
    // SARIMAX weather forecasting, deployed independently on the VPS.
    // Left uncommented (unlike semaphore above) so an unset/blank
    // SARIMA_SERVICE_URL degrades gracefully via SarimaForecastService's
    // own "not_configured" check, rather than never being read at all.
    'sarima' => [
        'url' => env('SARIMA_SERVICE_URL'),
        'token' => env('SARIMA_SERVICE_TOKEN'),
        'timeout' => env('SARIMA_SERVICE_TIMEOUT', 30),
        // Separate from 'url' above: this gates whether the SARIMA card
        // shows in the UI at all, regardless of whether the Python service
        // is reachable. Off by default -- no real PAGASA weather data has
        // been imported yet, so there's nothing meaningful to forecast.
        // Flip to true (env SARIMA_FEATURE_ENABLED=true) once real data
        // is imported and the feature is ready to show.
        'feature_enabled' => env('SARIMA_FEATURE_ENABLED', false),
    ],

];
