<?php

namespace Creode\LaravelHubspotForms;

use Carbon\Carbon;
use Creode\LaravelHubspotForms\Exceptions\ContactAlreadyExistsException;
use Creode\LaravelHubspotForms\Exceptions\FieldIsEmptyException;
use Creode\LaravelHubspotForms\Exceptions\HubspotContactIdNotProvidedException;
use Creode\LaravelHubspotForms\Exceptions\HubspotContactNotFoundException;
use Creode\LaravelHubspotForms\Exceptions\HubspotNoteBodyNotProvidedException;
use Creode\LaravelHubspotForms\Exceptions\MissingRequiredFieldException;
use Creode\LaravelHubspotForms\Exceptions\NoFieldKeyProvidedException;
use HubSpot\Factory;
use Creode\LaravelHubspotForms\Exceptions\NoDataProvidedException;
use Creode\LaravelHubspotForms\Exceptions\HubspotOwnersNotFoundException;
use Creode\LaravelHubspotForms\Contracts\SubmissionInterface;
use HubSpot\Client\Crm\Contacts\ApiException;
use HubSpot\Client\Crm\Contacts\Model\CollectionResponseWithTotalSimplePublicObjectForwardPaging;
use HubSpot\Client\Crm\Contacts\Model\Error;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObject;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput;
use HubSpot\Client\Crm\Contacts\Model\Filter;
use HubSpot\Client\Crm\Contacts\Model\FilterGroup;
use HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest;
use HubSpot\Client\Crm\Objects\Notes\Model\SimplePublicObjectInputForCreate;

class LaravelHubspotAPIService implements SubmissionInterface
{

    public \HubSpot\Discovery\Discovery $hubspot;

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
            throw new HubspotOwnersNotFoundException('No owners found');
        }

        return $owners;
    }

    private function getFirstOwnerId()
    {
        return $this->getOwners()[0]['id'];
    }

    private function getContactFields()
    {
        return config('laravel-hubspot-forms.hubspot_contact_fields');
    }

    private function getCompanyFields()
    {
        return config('laravel-hubspot-forms.hubspot_company_fields');
    }

    public function getPrimaryContactOwner()
    {
        return $this->hubspot->crm()->owners()->ownersApi()->getById(config('laravel-hubspot-forms.hubspot_primary_contact_owner_id'));
    }

    /**
     * @param array $data Array of user data to be validated
     * @return void
     * @throws \Exception
     */
    private function validate(array $data)
    {
        if (!$data) {
            throw new NoDataProvidedException('No data provided');
        }

        foreach ($this->getContactFields() as $field) {
            if (!isset($data[$field])) {
                throw new MissingRequiredFieldException('Missing required field: '.$field);
            }
        }
    }

    /**
     * @param array $userData Array of fields to be set on the contact
     * @return array Properties array to be used in the API request
     */
    private function setContactFields(array $userData)
    {
        $properties = [];

        foreach ($this->getContactFields() as $field) {
            $properties[$field] = $userData[$field];
        }

        return $properties;
    }

    private function assignNoteToContact(int $noteId, int $contactId)
    {
        try{
            return $this->hubspot->apiRequest([
                'method' => 'PUT',
                'path' => '/crm/v3/objects/notes/'.$noteId.'/associations/contact/'.$contactId.'/202',
            ]);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    private function assignOwnerToContact(array $userData, int $contactId)
    {
        try{
            $primaryContactOwner = $this->getPrimaryContactOwner();
            return $this->hubspot->apiRequest([
                'method' => 'PATCH',
                'path' => '/crm/v3/objects/contacts/'.$contactId,
                'body' => ['properties' => ['hubspot_owner_id' => $primaryContactOwner['id']]],
            ]);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    public function assignCompanyToContact(int $companyId, int $contactId)
    {
        try{
            return $this->hubspot->apiRequest([
                'method' => 'PUT',
                'path' => '/crm/v3/objects/companies/'.$companyId.'/associations/contact/'.$contactId.'/280',
            ]);
        } catch (\Exception $e) {
            throw new \Exception($e);
        }
    }

    /**
     * @param int $contactId The Hubspot ID of the contact the note should be associated to
     * @param string $noteBody This is the content of the note
     */
    public function createNote(int $contactId, string $noteBody)
    {
        if(!$contactId){
            throw new HubspotContactIdNotProvidedException('HubSpot Contact ID not provided');
        }

        if(!$noteBody){
            throw new HubspotNoteBodyNotProvidedException('Note body not provided');
        }

        $activityProperties = new SimplePublicObjectInputForCreate();

        $activityProperties->setProperties([
            'hs_timestamp' => Carbon::now('UTC'),
            'hs_note_body' => $noteBody,
            'hubspot_owner_id' => $this->getFirstOwnerId(),
        ]);

        $note = $this->hubspot->crm()->objects()->notes()->basicApi()->create($activityProperties);

        return $this->assignNoteToContact($note->getId(), $contactId);
    }

    /**
     * @param array $companyData Array of data used to create a company in HubSpot
     */
    public function createCompany(array $companyData)
    {
        // Seatch for Company by domain
        $companies = $this->findCompanyByKey('domain', $companyData['domain']);

        if( count($companies) >= 1 ){
            throw new CompanyAlreadyExistsException('Company already exists.');
        }

        $companyInput = new \HubSpot\Client\Crm\Companies\Model\SimplePublicObjectInput();
        $companyInput->setProperties($this->setCompanyFields($companyData));

        return $this->hubspot->crm()->companies()->basicApi()->create($companyInput);
    }

    /**
     * @param  array  $userData  Array of user data to be added
     * @return Error|SimplePublicObject
     * @throws ContactAlreadyExistsException|ApiException
     */
    public function createContact(array $userData)
    {
        $user = $this->findContactByKey('email', $userData['email']);

        if($user){
            throw new ContactAlreadyExistsException('Contact already exists in HubSpot.');
        }

        $contactInput = new SimplePublicObjectInput();
        $contactInput->setProperties($this->setContactFields($userData));

        $contact = $this->hubspot->crm()->contacts()->basicApi()->create($contactInput);

        // Create new ContactCouldNotBeCreated exception

        return $this->assignOwnerToContact($userData, $contact->getId());
    }

    /**
     * @param array $userData Array of user data to be updated
     * @param int $contactId  The Hubspot ID of the contact to be updated
     * @return Error|SimplePublicObject
     * @throws ApiException
     */
    public function updateContact(array $userData, int $contactId)
    {
        $this->validate($userData);

        $contactProperties = new SimplePublicObjectInput();

        $contactProperties->setProperties($this->setContactFields($userData));

        return $this->hubspot->crm()->contacts()->basicApi()->update($contactId, $contactProperties);
    }

/**
     * @param string $field The Hubspot field to search by
     * @param string $data The value to search for
     * @return CollectionResponseWithTotalSimplePublicObjectForwardPaging|Error
     */
    public function findContact(string $field, string $data)
    {
        if(!$field){
            throw new NoFieldKeyProvidedException('No field key provided');
        }

        $filter = new \HubSpot\Client\Crm\Contacts\Model\Filter();
        $filter
            ->setOperator('EQ')
            ->setPropertyName($field)
            ->setValue($data);

        $filterGroup = new \HubSpot\Client\Crm\Contacts\Model\FilterGroup();
        $filterGroup->setFilters([$filter]);

        $searchRequest = new \HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest();
        $searchRequest->setFilterGroups([$filterGroup]);

        $searchRequest->setProperties($this->getContactFields());

        return $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);
    }

    /**
     * @param string $field The Hubspot field to search by
     * @param string $data The value to search for
     * @return CollectionResponseWithTotalSimplePublicObjectForwardPaging|Error
     */
    public function findCompany(string $field, string $data)
    {
        if(!$field){
            throw new NoFieldKeyProvidedException('No field key provided');
        }

        $filter = new \HubSpot\Client\Crm\Companies\Model\Filter();
        $filter
            ->setOperator('EQ')
            ->setPropertyName($field)
            ->setValue($data);

        $filterGroup = new \HubSpot\Client\Crm\Companies\Model\FilterGroup();
        $filterGroup->setFilters([$filter]);

        $searchRequest = new \HubSpot\Client\Crm\Companies\Model\PublicObjectSearchRequest();
        $searchRequest->setFilterGroups([$filterGroup]);

        return $this->hubspot->crm()->companies()->searchApi()->doSearch($searchRequest);
    }

    /**
     * @param string $key The key to search by
     * @param string $data The value to search for
     * @return array
     */
    public function findContactByKey(string $key, string $data) : array
    {
        $contact = $this->findContact($key, $data);

        return $contact->getResults();
    }

    /**
     * @param string $key The key to search by
     * @param string $data The value to search for
     * @return array
     */
    public function findCompanyByKey(string $key, string $data) : array
    {
        $contact = $this->findCompany($key, $data);

        return $contact->getResults();
    }

    /**
     * @param array $contactResults
     * @return int|HubspotContactNotFoundException
     */
    public function getContactId(array $contactResults) : int|HubspotContactNotFoundException
    {
        if(!$contactResults){
            throw new HubspotContactNotFoundException('Contact not found');
        }

        return $contactResults[0]->getId();
    }
}
