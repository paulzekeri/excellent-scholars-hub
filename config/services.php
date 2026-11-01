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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Card payments (future): the parent form shows "Card - coming soon" until
    | both keys are set. See App\Services\PaystackService.
    */
    'paystack' => [
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    /*
    | Free wording assistant for learning goals (LanguageTool). No key is needed
    | for the public endpoint; point the URL at a self-hosted instance to avoid
    | the public rate limit, or set GOAL_WORDING_ASSISTANT=false to switch it off.
    */
    'languagetool' => [
        'enabled' => env('GOAL_WORDING_ASSISTANT', true),
        'url' => env('LANGUAGETOOL_URL', 'https://api.languagetool.org/v2/check'),
        'language' => env('LANGUAGETOOL_LANGUAGE', 'en-US'),
        'timeout' => (int) env('LANGUAGETOOL_TIMEOUT', 15),
    ],

];

