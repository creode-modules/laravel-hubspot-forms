---
outline: deep
---

# Using the Blade Component
This blade component has 2 parameters. `form-id` and `portal-id`. 

Both of these IDs can be found in you HubSpot account in the ***Forms*** section when editing your form.
```HTML
<x-hubspot-form form-id="your-hubspot-form-id-here" portal-id="your-hubspot-port-id-here" />
```
Replace the placeholder text with your `form-id` and you `portal-id`.

The form will then be rendered on the page.