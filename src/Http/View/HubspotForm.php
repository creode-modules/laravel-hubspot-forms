<?php

namespace Creode\LaravelHubspotForms\Http\View;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HubspotForm extends Component
{
    public function __construct(
        public string $formId,
        public string $portalId,
        public string $region = "eu1"
    )
    {
    }

    public function render(): View
    {
        return view('laravel-hubspot-forms::hubspot-form');
    }
}
