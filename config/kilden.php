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
    | Frontend snippet (@kildenScript)
    |--------------------------------------------------------------------------
    | @kildenScript renders the web SDK loader in your Blade layout. This is
    | the PUBLIC wk_ key — a separate entry on purpose, so the secret sk_ key
    | above can never leak into a view. Unset = the directive renders nothing.
    */
    'frontend' => [
        'write_key' => env('KILDEN_PUBLIC_WRITE_KEY'),
        'cdn' => env('KILDEN_CDN', 'https://cdn.kilden.io/kilden.iife.js'),
        // Extra kilden.init options rendered as JSON (e.g. 'debug' => true).
        'options' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Identity verification
    |--------------------------------------------------------------------------
    | Secret + key id for signing identity tokens (kilden.io/docs/identity).
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
