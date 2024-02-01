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
        $data = $event->data;
        $hubspot = new \Creode\LaravelHubspotForms\LaravelHubspotAPIService();

        $contactId = $hubspot->find('email', $data['email'])
            ->getResults()[0]
            ->getId();

//        $hubspot->updateUser($data, $contactId);

        $hubspot->createNote(
            $contactId,
            $data['note']
        );
    }
}
