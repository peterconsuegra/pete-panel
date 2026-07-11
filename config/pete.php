<?php

return [

    /*
    |--------------------------------------------------------------------------
    | License validation (v2, signed grants)
    |--------------------------------------------------------------------------
    |
    | The store at PETE_DASHBOARD_URL signs license grants with an Ed25519
    | private key that never leaves it. This public key verifies those
    | grants offline. Grants are short-lived and renewed automatically.
    |
    */

    'license_public_key' => env('PETE_LICENSE_PUBLIC_KEY', ''),

];
