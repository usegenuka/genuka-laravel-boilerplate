<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Genuka API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Genuka API. This is used for all API requests
    | including OAuth token exchange and company data retrieval.
    |
    */
    'url' => env('GENUKA_URL', 'https://api-staging.genuka.com'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client ID
    |--------------------------------------------------------------------------
    |
    | Your Genuka OAuth client ID. You can get this from your Genuka
    | developer dashboard.
    |
    */
    'client_id' => env('GENUKA_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Client Secret
    |--------------------------------------------------------------------------
    |
    | Your Genuka OAuth client secret. Keep this secret and never commit
    | it to version control.
    |
    */
    'client_secret' => env('GENUKA_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | OAuth Redirect URI
    |--------------------------------------------------------------------------
    |
    | The redirect URI registered in your Genuka OAuth application.
    | This should match the callback endpoint in your application.
    |
    */
    'redirect_uri' => env('GENUKA_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Token Encryption
    |--------------------------------------------------------------------------
    |
    | Enable encryption for storing access tokens in the database.
    | Highly recommended for production environments.
    |
    */
    'encrypt_tokens' => env('GENUKA_ENCRYPT_TOKENS', true),
];
