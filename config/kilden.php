<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Write key (SECRET)
    |--------------------------------------------------------------------------
    | The sk_ key from your project settings. Never the public wk_ key: events
    | sent with the secret key are source=server, verified=true — facts.
    */
    'write_key' => env('KILDEN_WRITE_KEY'),

    'host' => env('KILDEN_HOST', 'https://ingest.kilden.io'),

    // false turns the whole SDK into a no-op (tests, CI, local dev).
    'enabled' => env('KILDEN_ENABLED', true),

    'debug' => env('KILDEN_DEBUG', false),

    'options' => [
        'flush_at' => 20,
        'flush_interval' => 10,
        'max_queue_size' => 10000,
        'timeout' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Queued delivery
    |--------------------------------------------------------------------------
    | true = track()/identify()/alias() dispatch a queued job instead of
    | sending inline. Recommended in production: the HTTP request to Kilden
    | happens on a worker, never inside your user's request.
    */
    'queue' => [
        'enabled' => env('KILDEN_QUEUE', false),
        'connection' => env('KILDEN_QUEUE_CONNECTION'),
        'queue' => env('KILDEN_QUEUE_NAME'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity verification
    |--------------------------------------------------------------------------
    | Secret + key id for signing identity tokens (docs.kilden.io/identity).
    | Used by the POST /kilden/identity endpoint and Kilden::identityToken().
    */
    'identity' => [
        'secret' => env('KILDEN_IDENTITY_SECRET'),
        'kid' => env('KILDEN_IDENTITY_KID', 'k1'),
        'ttl' => 3600,
        // Route middleware for the identity endpoint.
        'middleware' => ['web', 'auth'],
    ],

];
