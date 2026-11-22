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

];
