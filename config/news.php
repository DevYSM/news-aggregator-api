<?php

return [
    'newsapi' => [
        'key' => env('NEWSAPI_KEY'),
        'country' => env('NEWSAPI_COUNTRY', 'us'),
        'page_size' => (int) env('NEWSAPI_PAGE_SIZE', 100),
    ],

    'guardian' => [
        'key' => env('GUARDIAN_KEY'),
        'page_size' => (int) env('GUARDIAN_PAGE_SIZE', 50),
    ],

    'nyt' => [
        'key' => env('NYT_KEY'),
    ],
];
