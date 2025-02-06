---
outline: deep
---

# Configuration

The `laravel-hubspot-forms` config file contains values including accepted form fields to the access token for the HubSpot API.

[[toc]]

## HubSpot API

### Base URL

```PHP
/*
|--------------------------------------------------------------------------
| HubSpot Base URL
|--------------------------------------------------------------------------
| This is the base URL for the HubSpot API.
| This is usually https://api.hubapi.com
|
*/
'hubspot_base_url' => env('HUBSPOT_BASE_URL'),
```

The base URL for the HubSpot API is currently `https://api.hubapi.com`. At the time of writing, this is the same for all HubSpot API endpoints. However, if this changes in the future this config variable can be used to update the base URL.

### Access Token

```PHP
/*
|--------------------------------------------------------------------------
| HubSpot Access Token
|--------------------------------------------------------------------------
| This is the access token for your HubSpot Private app. This can be
| generated from your HubSpot account.
|
*/
'access_token' => env('HUBSPOT_ACCESS_TOKEN'),
```

If you haven't got a HubSpot Private App Access Token, [click here](https://developers.hubspot.com/docs/guides/apps/private-apps/overview#make-api-calls-with-your-app-s-access-token) and follow the instructions.

## Fields
Fields for both Contacts and Companies are stored in config. This is to prevent rogue data from being added to the request.

These field names correspond to the property names in HubSpot.

A full list of property names can found either on HubSpot's API documentation or in the HubSpot dashbaord.

### Contact Fields
These field names are used when creating or updating Contacts.
::: danger NOTE
When creating a new contact, you should include at least one of the following properties in your request: **email**, **firstname**, or **lastname**. It is recommended to always include **email**, because email address is the primary unique identifier to avoid duplicate contacts in HubSpot.
:::
```PHP
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
```

### Company Fields {#company-fields}
These field names are used when creating Companies.
::: danger NOTE
When creating a new company, you should include at least one of the following properties in your request: **name** or **domain**. It is recommended to always include **domain**, because domain names are the primary unique identifier to avoid duplicate companies in HubSpot.
:::
```PHP
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
```

Getters and setters are then used in the Service Class to access these fields.

## Creating a Company
```PHP
/*
|--------------------------------------------------------------------------
| Create HubSpot Company Using This Field
|--------------------------------------------------------------------------
| This is the field that will be used to create a new company in HubSpot.
| This can be either 'name' or 'domain'. 
|
*/
'create_hubspot_company_using' => env('CREATE_HUBSPOT_COMPANY_USING', 'name'),
```

A HubSpot company can be created using using either, the `name` field, the `domain` field or both. But at least one of these fields must be present in the request. This config variable is used to define which field to use. 


For example, the name field is used by default becuase not all business have a website. However, this method is not 100% accurate when trying to associating a contact with a company. As a company name is not a unique identifyer in HubSpot. This means it is possilbe to have 2 compaines with the same name. When this occurs it is not possible for this package to associate a contact with a company as it is impossible to know which is the correct company.

The `domain` field however, **Is** a unique identifyer in HubSpot. This is why HubSpot reccomends including the `domain` field in the request. If the domain field from the request matches the domain value for the company in HubSpot, then the contact will be associated with the company consistently. 

If you do include the `domain` field in the request, make sure you set this value in your `.env` file as `domain`. Also make sure you include the `domain` field in the `hubspot_company_fields` array mentioned [here](#company-fields). 

## Primary Contact Owner

```PHP
/*
|--------------------------------------------------------------------------
| HubSpot Primary Contact Owner ID
|--------------------------------------------------------------------------
| This is the ID of the primary contact owner in HubSpot to assign to all
| newly created contacts.
|
*/
'hubspot_primary_contact_owner_id' => env('HUBSPOT_PRIMARY_CONTACT_OWNER_ID', "230928667"),
```

This ID is used to set the Primary Contact Owner in HubSpot for all newly created contacts.

This feature was added to remove the manual process of performing this task in the HubSpot dashboard.