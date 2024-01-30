<?php

namespace Creode\LaravelHubspotForms;

use Carbon\Carbon;
use Creode\LaravelHubspotForms\Contracts\SubmissionInterface;
use http\Exception;
use HubSpot\Client\Crm\Contacts\ApiException;
use HubSpot\Client\Crm\Contacts\Model\Error;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObject;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;
use HubSpot\Client\Crm\Contacts\Model\Filter;
use HubSpot\Client\Crm\Contacts\Model\FilterGroup;
use HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest;
use HubSpot\Client\Crm\Objects\Notes\Model\SimplePublicObjectInput as ActivityInput;
use HubSpot\Factory;

class LaravelHubspotAPIServiceProvider implements SubmissionInterface
{

    public \HubSpot\Discovery\Discovery $hubspot;

    protected array $contactFields = [
        'email',
        'firstname',
        'lastname',
    ];

    public function __construct()
    {
        $this->hubspot = Factory::createWithAccessToken(config('laravel-hubspot-forms.access_token'));
    }

    public function getOwners()
    {
        return $this->hubspot->crm()->owners()->getAll();
    }

    public function createNote($contactId, $noteBody)
    {
        $activityProperties = new ActivityInput();

        $ownerId = $this->getOwners()[0]['id'];

        $activityProperties->setProperties([
            'hs_timestamp' => Carbon::now('UTC'),
            'hs_note_body' => 'New Wishlist created: <a href="https://awdis.creode.co.uk/wishlist/wishlist-id-here">View Wishlist</a>',
            'hubspot_owner_id' => $ownerId,
        ]);

        $note = $this->hubspot->crm()->objects()->notes()->basicApi()->create($activityProperties);

        return $this->hubspot->apiRequest([
            'method' => 'PUT',
            'path' => '/crm/v3/objects/notes/'.$note->getId().'/associations/contact/'.$contactId.'/202',
        ]);
    }

    /**
     * @param  array  $user  Required fields are email, firstname, lastname
     * @param  int  $contactId  The Hubspot ID of the contact the note should be associated to
     * @return Error|SimplePublicObject
     * @throws ApiException
     */
    public function update($user, $contactId)
    {
        $this->validate($user);

        $contactProperties = new SimplePublicObjectInput();

        $contactProperties->setProperties($this->setContactFields($user));

        return $this->hubspot->crm()->contacts()->basicApi()->update($contactId, $contactProperties);
    }

    public function find($field, $email)
    {
        $filter = new Filter();
        $filter
            ->setOperator('EQ')
            ->setPropertyName($field)
            ->setValue($email);

        $filterGroup = new FilterGroup();
        $filterGroup->setFilters([$filter]);

        $searchRequest = new PublicObjectSearchRequest();
        $searchRequest->setFilterGroups([$filterGroup]);

        $searchRequest->setProperties($this->contactFields);

        return $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);
    }

    // Validate fields
    public function validate($data)
    {
        if (!$data) {
            throw new \Exception('No data provided');
        }

        foreach ($this->contactFields as $field) {
            if (!isset($data[$field])) {
                throw new \Exception('Missing required field: '.$field);
            }

            if (!$data[$field]) {
                throw new \Exception('Field '.$field.' is empty.');
            }
        }
    }

    protected function setContactFields($user)
    {
        $fields = array_keys($user);

        $properties = [];
        foreach ($fields as $field) {
            $properties[$field] = $user[$field];
        }

        return $properties;
    }
}
