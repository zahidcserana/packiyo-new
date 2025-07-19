<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Crstl EDI API base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Crstl EDI API used to generate GS!-128 labels,
    | submit ASNs, and other EDI transactions.
    |
    */
    'api_base_url' => env('CRSTL_EDI_PROD_BASE_URL', 'https://api.crstl.so'),
    'sandbox_api_base_url' => env('CRSTL_EDI_SANDBOX_BASE_URL', 'https://sandbox-api.crstl.so'),
];
