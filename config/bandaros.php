<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API rate limit
    |--------------------------------------------------------------------------
    |
    | Requests per minute each team (shop) may make to the API, shared by all
    | of its members and tokens. One busy or misbehaving shop can't slow
    | down the others.
    |
    */

    'api_rate_limit' => (int) env('API_RATE_LIMIT', 120),

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | What each plan allows. null means unlimited. There are no payments yet:
    | the shop owner switches plans on the Team page.
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free',
            'max_products' => 100,
            'max_members' => 2,
            'import' => false,
        ],
        'pro' => [
            'name' => 'Pro',
            'max_products' => null,
            'max_members' => null,
            'import' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Background work
    |--------------------------------------------------------------------------
    |
    | Exports with more rows, and import files bigger than this, run on the
    | queue (php artisan queue:work); the user is notified when they finish.
    | Smaller ones run straight away.
    |
    */

    'queue_exports_over_rows' => (int) env('QUEUE_EXPORTS_OVER_ROWS', 500),

    'queue_imports_over_kb' => (int) env('QUEUE_IMPORTS_OVER_KB', 256),

    // Finished export files are kept this long, then removed by the scheduler.
    'keep_exports_for_days' => 7,

];
