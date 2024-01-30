<?php

namespace Creode\LaravelHubspotForms;

use Carbon\Carbon;
use Creode\LaravelHubspotForms\Contracts\SubmissionInterface;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;
use HubSpot\Client\Crm\Contacts\Model\Filter;
use HubSpot\Client\Crm\Contacts\Model\FilterGroup;
use HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest;
use HubSpot\Client\Crm\Objects\Notes\Model\SimplePublicObjectInput as ActivityInput;
use HubSpot\Client\Crm\Associations\Model\BatchInputPublicAssociation;
use HubSpot\Factory;

class LaravelHubspotAPIServiceProvider implements SubmissionInterface
{

    public \HubSpot\Discovery\Discovery $hubspot;

    protected array $contactFields = [
        'email',
        'firstName',
        'lastName',
        'company',
    ];

    public function __construct()
    {
        $this->hubspot = Factory::createWithAccessToken(config('laravel-hubspot-forms.access_token'));
    }

    public function update($user, $contactId)
    {
        $contactProperties = new SimplePublicObjectInput();

        $contactProperties->setProperties($this->setContactFields($user));

        return $this->hubspot->crm()->contacts()->basicApi()->update($contactId, $contactProperties);
    }

    public function getOwners()
    {

        return $this->hubspot->crm()->owners()->getAll();
    }

    public function createNote($contactId)
    {
        $activityProperties = new ActivityInput();

        $ownerId = $this->getOwners()[0]['id'];

        $activityProperties->setProperties([
            'hs_timestamp' => Carbon::now('UTC'),
            'hs_note_body' => 'New Wishlist created. <br> <a href="https://awdis.creode.co.uk/wishlist/wishlist-id-here">View Wishlist</a>',
            'hubspot_owner_id' => $ownerId,
        ]);

        $note = $this->hubspot->crm()->objects()->notes()->basicApi()->create($activityProperties);

        return $this->hubspot->apiRequest([
            'method' => 'PUT',
            'path' => '/crm/v3/objects/notes/'.$note->getId().'/associations/contact/'.$contactId.'/202',
        ]);

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
