<?php

namespace Creode\LaravelHubspotForms\Listeners;

class SendDataToHubspot
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        dd('Test');
//        $hubspot = new \Creode\LaravelHubspotForms\LaravelHubspotAPIService();
//
//        $contactId = $hubspot->find('email', $userData['email'])
//            ->getResults()[0]
//            ->getId();
//
//        $hubspot->updateUser($userData, $contactId);

        // Update user data if needed
        // Create note with link to wishlist
        // Associate the note with the user
    }
}
