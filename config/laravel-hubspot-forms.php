<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HubSpot Access Token
    |--------------------------------------------------------------------------
    | This is the access token for your HubSpot account. This can be generated
    | in your HubSpot account settings.
    |
    */
    'access_token' => env('HUBSPOT_ACCESS_TOKEN'),
    /*
    |--------------------------------------------------------------------------
    | HubSpot Base URL
    |--------------------------------------------------------------------------
    | This is the base URL for the HubSpot API.
    | This is usually https://api.hubapi.com
    |
    */
    'hubspot_base_url' => env('HUBSPOT_BASE_URL'),
    /*
    |--------------------------------------------------------------------------
    | HubSpot Contact Fields
    |--------------------------------------------------------------------------
    | These are the fields that are required to create or update a contact
    | using the HubSpot API
    |
    */
    'hubspot_contact_fields' => [
        'email',
        'firstname',
        'lastname',
    ],
];
