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
        'company',
        'newsletter_sign_up'
    ],
    /*
    |--------------------------------------------------------------------------
    | HubSpot Company Fields
    |--------------------------------------------------------------------------
    | These are the fields that are required to create or update a company
    | using the HubSpot API. Either the 'name' or 'domain' field is required.
    |
    */
    'hubspot_company_fields' => [
        'name',
        'domain'
    ],
    /*
    |--------------------------------------------------------------------------
    | Create HubSpot Company Using This Field
    |--------------------------------------------------------------------------
    | This is the field that will be used to create a new company in HubSpot.
    | This can be either 'name' or 'domain'. 
    |
    */
    'create_hubspot_company_using' => env('CREATE_HUBSPOT_COMPANY_USING', 'name'),
    /*
    |--------------------------------------------------------------------------
    | HubSpot Primary Contact Owner ID
    |--------------------------------------------------------------------------
    | This is the ID of the primary contact owner in HubSpot to assign to all
    | newly created contacts.
    |
    */
    'hubspot_primary_contact_owner_id' => env('HUBSPOT_PRIMARY_CONTACT_OWNER_ID', '230928667'),
];
