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
use HubSpot\Client\Crm\Objects\Notes\Model\SimplePublicObjectInputForCreate;
use HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectWithAssociations;
use HubSpot\Client\Crm\Companies\Model\SimplePublicObjectWithAssociations as SimplePublicCompaniesObjectWithAssociations;

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

    public function getPrimaryContactOwnerId()
    {
        return $this->hubspot->crm()->owners()->ownersApi()->getById(config('laravel-hubspot-forms.hubspot_primary_contact_owner_id'));
    }

    public function getContactOwnerId(int $contactId)
    {
        $contact = $this->hubspot->crm()->contacts()->basicApi()->getById($contactId, 'hubspot_owner_id');
        return $contact['properties']['hubspot_owner_id'];
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

    /**
     * @param array $userData Array of fields to be set on the contact
     * @return array Properties array to be used in the API request
     */
    private function setCompanyFields(array $userData)
    {
        $properties = [];

        foreach ($this->getCompanyFields() as $field) {
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

    public function assignOwnerToContact(int $contactId)
    {
        try{
            $primaryContactOwner = $this->getPrimaryContactOwnerId();
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

    public function createCompany(array $companyData)
    {
        // Search for Company by domain
        $companies = $this->findCompanyByKey('domain', $companyData['domain']);

        if( $companies ){
            throw new CompanyAlreadyExistsException('Company already exists.');
        }

        $companyInput = new \HubSpot\Client\Crm\Companies\Model\SimplePublicObjectInput();
        $companyInput->setProperties($this->setCompanyFields($companyData));

        return $this->hubspot->crm()->companies()->basicApi()->create($companyInput);
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
     * @param  array  $userData  Array of user dat to be added
     * @return Error|SimplePublicObject
     * @throws ContactAlreadyExistsException|ApiException
     */
    public function createContact(array $userData)
    {
        $contacts = $this->findContactByKey('email', $userData['email']);

        if( $contacts ){
            throw new ContactAlreadyExistsException('Contact already exists in HubSpot.');
        }

        $contactInput = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput();
        $contactInput->setProperties($this->setContactFields($userData));

        return $this->hubspot->crm()->contacts()->basicApi()->create($contactInput);
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

        $contactProperties = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput();

        $contactProperties->setProperties($this->setContactFields($userData));

        return $this->hubspot->crm()->contacts()->basicApi()->update($contactId, $contactProperties);
    }

    /**
     * @param string $key The key to search by
     * @param string $data The value to search for
     * @return SimplePublicObjectWithAssociations
     */
    public function getContactByKey(string $key, string $data) : SimplePublicObjectWithAssociations
    {
        return $this->hubspot->crm()->contacts()->basicApi()->getById($data, null, null, null, false, $key);
    }

    /**
     * @param string $field The Hubspot field to search by
     * @param string $data The value to search for
     * @return CollectionResponseWithTotalSimplePublicObjectForwardPaging|Error
     */
    public function findContactByKey(string $field, string $data)
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

        $contacts = $this->hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);

        return $contacts->getResults();
    }

    /**
     * @param string $field The Hubspot field to search by
     * @param string $data The value to search for
     * @return CollectionResponseWithTotalSimplePublicObjectForwardPaging|Error
     */
    public function findCompanyByKey(string $field, string $data)
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

        $compaines = $this->hubspot->crm()->companies()->searchApi()->doSearch($searchRequest);
        return $compaines->getResults();
    }
}
