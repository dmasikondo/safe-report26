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

];
