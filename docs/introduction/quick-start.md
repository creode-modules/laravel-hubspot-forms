---
outline: deep
---

# Quick Start
## Installation
To get started, please run the following command in the Laravel application.

```BASH
composer require creode/laravel-hubspot-forms
````
::: info API Only
If do not intend to use the HubSpot Embedded Forms Blade Component, please skip to the next step [Configuration](#configuration).
:::

### Add JavaScript <Badge type="tip" text="HubSpot Embedded Forms Only" />
Add the following **script tag** to your layout file. This is required for the HubSpot embedded form to be rendered.

```HTML
<script charset="utf-8" type="text/javascript" src="//js-eu1.hsforms.net/forms/embed/v2.js"></script>
```

## Configuration {#configuration}
Add the following environment variables to your `.env` file and replace `your-access-token-here` with your HubSpot Private App Access Token.
```DOTENV
HUBSPOT_ACCESS_TOKEN="your-access-token-here"
HUBSPOT_BASE_URL="hubspot-base-url"
```
The base URL is usually `https://api.hubapi.com`.
::: tip
If you haven't got a HubSpot Private App Access Token, [click here](https://developers.hubspot.com/docs/guides/apps/private-apps/overview#make-api-calls-with-your-app-s-access-token) and follow the instructions.
:::

### Publish Views & Config File
This step is necessary to use either the API wrapper or the Embedded Forms Component. 

Please run the following command in your Laravel application.
```BASH
php artisan vendor:publish --provider="Creode\LaravelHubspotForms\LaravelHubspotFormsServiceProvider"
```
This will publish both the HubSpot Embedded Forms Blade Component and the config file for the package.