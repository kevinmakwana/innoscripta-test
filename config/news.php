<?php

return [
    'newsapi' => [
        'key' => env('NEWSAPI_KEY'),
        'timeout' => env('NEWS_TIMEOUT', 10),
        'retry_attempts' => env('NEWS_RETRY_ATTEMPTS', 3),
        'retry_sleep_ms' => env('NEWS_RETRY_SLEEP_MS', 100),
        'retry_max_sleep_ms' => env('NEWS_RETRY_MAX_SLEEP_MS', 2000),
    ],
    'nyt' => [
        'key' => env('NYT_API_KEY'),
        'timeout' => env('NEWS_TIMEOUT', 10),
        'retry_attempts' => env('NEWS_RETRY_ATTEMPTS', 3),
    ],
    'guardian' => [
        'key' => env('GUARDIAN_API_KEY'),
        'timeout' => env('NEWS_TIMEOUT', 10),
        'retry_attempts' => env('NEWS_RETRY_ATTEMPTS', 3),
    ],
    'batch_size' => env('NEWS_BATCH_SIZE', 50),
    'failure_threshold' => env('NEWS_FAILURE_THRESHOLD', 3),
    // Enable Postgres pg_trgm-based deduplication when true. Keep false in
    // development by default to avoid requiring the extension during local dev.
    'use_pg_trgm' => env('NEWS_USE_PG_TRGM', false),
];
