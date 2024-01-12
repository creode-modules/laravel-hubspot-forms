# Laravel HubSpot Forms
A Laravel package that allows you to embed a HubSpot form into your Laravel application using a Blade component

## Installation
`composer require creode/laravel-hubspot-forms`

Then, add the following **script tag** to your layout file. This is required for the HubSpot form to be rendered.

`<script charset="utf-8" type="text/javascript" src="//js-eu1.hsforms.net/forms/embed/v2.js"></script>`

## Usage
Blade component:
`<x-hubspot-form form-id="youre-hubspot-form-id-here" portal-id="your-hubspot-form-id-here" />`
