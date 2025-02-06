---
outline: deep
---

# Examples
## Dependency Inject Service Class

This is an example use case for this package. Injecting the `LaravelHubspotAPIService` in the `__constuct` method of an event listener. 

An event is then triggered following a form submission. 

```PHP
use Creode\LaravelHubspotForms\LaravelHubspotAPIService;

class RegisterHubspotContact
{
    /**
     * Create the event listener.
     */
    public function __construct(protected LaravelHubspotAPIService $hubspot)
    {
        //
    }
```