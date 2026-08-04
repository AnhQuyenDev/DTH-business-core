<?php

return [
    'supported' => [
        'en' => 'English',
        'vi' => 'Tiếng Việt',
    ],
    'default' => env('APP_LOCALE', 'en'),
    'fallback' => env('APP_FALLBACK_LOCALE', 'en'),
];
