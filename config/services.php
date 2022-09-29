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
    
    'firebase' => [
        'api_key' => env('FIREBASE_API_KEY'),         // https://prnt.sc/26ta9j7
        'project_id' => env('FIREBASE_PROJECT_ID'),   // https://prnt.sc/26ta7v8
        'username' => env('FIREBASE_USERNAME'),
        'password' => env('FIREBASE_PASSWORD'),
        'database' => env('FIREBASE_DEFAULT_DATABASE'),
    ],

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
    
    'waitwhile' => [
        'apikey' => env('WAITWHILE_API_KEY'),
    ],

];
