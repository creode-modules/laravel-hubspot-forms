<?php

namespace Creode\LaravelHubspotForms;

use Carbon\Carbon;
use Creode\LaravelHubspotForms\Contracts\SubmissionInterface;
use HubSpot\Client\Crm\Contacts\ApiException;
use HubSpot\Client\Crm\Contacts\Model\CollectionResponseWithTotalSimplePublicObjectForwardPaging;
use HubSpot\Client\Crm\Contacts\Model\Error;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObject;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;
use HubSpot\Client\Crm\Contacts\Model\Filter;
use HubSpot\Client\Crm\Contacts\Model\FilterGroup;
use HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest;
use HubSpot\Client\Crm\Objects\Notes\Model\SimplePublicObjectInput as ActivityInput;
use HubSpot\Factory;

class LaravelHubspotAPIService implements SubmissionInterface
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

    /**
     * @return array Array of owners of the HubSpot account
     * @throws \Exception
     */
    private function getOwners()
    {
        $owners = $this->hubspot->crm()->owners()->getAll();

        if(!$owners){
            throw new \Exception('No owners found');
        }

        return $owners;
    }

    /**
     * @param array $data Array of user data to be validated
     * @return void
     * @throws \Exception
     */
    private function validate($data)
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

    /**
     * @param array $userData Array of fields to be set on the contact
     * @return array Properties array to be used in the API request
     */
    private function setContactFields($userData)
    {
        $fields = array_keys($userData);

        $properties = [];
        foreach ($fields as $field) {
            $properties[$field] = $userData[$field];
        }

        return $properties;
    }

    /**
     * @param int $contactId The Hubspot ID of the contact the note should be associated to
     * @param string $noteBody This is the content of the note
     */
    public function createNote($contactId, $noteBody)
    {
        if(!$contactId){
            throw new \Exception('HubSpot Contact ID not provided');
        }

        if(!$noteBody){
            throw new \Exception('Note body not provided');
        }

        $activityProperties = new ActivityInput();

        $ownerId = $this->getOwners()[0]['id'];

        $activityProperties->setProperties([
            'hs_timestamp' => Carbon::now('UTC'),
            'hs_note_body' => $noteBody,
            'hubspot_owner_id' => $ownerId,
        ]);

        // Maybe try catch on create note.
        $note = $this->hubspot->crm()->objects()->notes()->basicApi()->create($activityProperties);

        if(!$note){
            throw new \Exception('Note not created');
        }

        return $this->assignNoteToContact($note, $contactId);
    }

    private function assignNoteToContact($note, $contactId)
    {
        return $this->hubspot->apiRequest([
            'method' => 'PUT',
            'path' => '/crm/v3/objects/notes/'.$note->getId().'/associations/contact/'.$contactId.'/202',
        ]);
    }

    /**
     * @param array $userData Array of user data to be updated
     * @param int $contactId  The Hubspot ID of the contact to be updated
     * @return Error|SimplePublicObject
     * @throws ApiException
     */
    public function updateUser($userData, $contactId)
    {
        $this->validate($userData);

        $contactProperties = new SimplePublicObjectInput();

        $contactProperties->setProperties($this->setContactFields($userData));

        return $this->hubspot->crm()->contacts()->basicApi()->update($contactId, $contactProperties);
    }

    /**
     * @param array $userData Array of user dat to be added
     * @return Error|SimplePublicObject
     */
    public function createContact($userData)
    {
        $contactInput = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput();
        $contactInput->setProperties($this->setContactFields($userData));

        return $this->hubspot->crm()->contacts()->basicApi()->create($contactInput);
    }

    /**
     * @param string $field The Hubspot field to search by
     * @param string $searchTerm The value to search for
     * @return CollectionResponseWithTotalSimplePublicObjectForwardPaging|Error
     */
    public function find($field, $searchTerm)
    {
        if(!$field){
            throw new \Exception('No field provided');
        }

        $filter = new Filter();
        $filter
            ->setOperator('EQ')
            ->setPropertyName($field)
            ->setValue($searchTerm);

        $filterGroup = new FilterGroup();
        $filterGroup->setFilters([$filter]);

        $searchRequest = new PublicObjectSearchRequest();
        $searchRequest->setFilterGroups([$filterGroup]);

        $searchRequest->setProperties($this->contactFields);

        return $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);
    }
}
