<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tor onion address
    |--------------------------------------------------------------------------
    |
    | The .onion address of this SafeReport installation's Tor hidden service.
    | When set, it is shown on the homepage so reporters can reach the
    | platform through the Tor Browser.
    |
    */

    'onion_url' => env('SAFEREPORT_ONION_URL'),

    /*
    |--------------------------------------------------------------------------
    | Report encryption key
    |--------------------------------------------------------------------------
    |
    | Report text and evidence files are encrypted with this key, kept
    | separate from APP_KEY. Generate it with `php artisan safereport:key`.
    | Back it up somewhere safe: if it is lost, reports cannot be decrypted.
    |
    */

    'encryption_key' => env('SAFEREPORT_ENCRYPTION_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Evidence attachments
    |--------------------------------------------------------------------------
    */

    'attachments' => [
        'max_files' => 2,
        'max_kilobytes' => 2048,
        'disk' => 'local',
    ],

];
