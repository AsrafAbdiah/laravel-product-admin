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
    | the shop owner switches plans on the Team page. The public pricing page
    | is built from this list too, so it always shows the real limits.
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 'RM 0',
            'period' => 'forever',
            'tagline' => 'For a new shop getting organised.',
            'max_products' => 100,
            'max_members' => 2,
            'import' => false,
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 'RM 49',
            'period' => 'per month',
            'tagline' => 'For a growing shop with a team.',
            'max_products' => null,
            'max_members' => null,
            'import' => true,
        ],
    ],

    // Linked from the public website's footer.
    'repository_url' => 'https://github.com/AsrafAbdiah/laravel-product-admin',

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
