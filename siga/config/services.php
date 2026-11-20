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

    /*
    |--------------------------------------------------------------------------
    | Hipolabs University Domains and Names API
    |--------------------------------------------------------------------------
    |
    | ES-02: directorio externo de universidades que respalda las
    | sugerencias de "institución de origen" en el formulario de
    | convalidación (ver ExternalInstitutionDirectoryInterface). Servicio
    | público sin llave de API — la única razón de que la URL esté aquí y
    | no como una constante en el adaptador es poder apuntarla a un mock
    | propio sin tocar código, igual que cualquier otro servicio externo
    | de este archivo.
    |
    */

    'hipolabs_universities' => [
        'url' => env('HIPOLABS_UNIVERSITIES_URL', 'http://universities.hipolabs.com/search'),
    ],

];
