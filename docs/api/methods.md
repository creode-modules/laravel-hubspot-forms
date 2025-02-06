---
outline: deep
---
# Methods

Here are just some of the methods from this package.
To see all the methods available please see the `LaravelHubspotAPIService.php` file.
[[toc]]

## Create

### `createContact()`
This method accepts an `array` and returns the newly created Contact as a `SimplePublicObject`.

This method takes the email field and performs a search for a contact using the `findContactByKey` method. 
If a contact is found, then a `ContactAlreadyExistsException` is thrown.

If no contact is found, then the data will be validated then passed into the request.

#### Usage
The keys in this array must match the field names set in the config on the previous page.
```PHP
$contactSubmissionData = [
    'email' => $event->user->email,
    'firstname' => $name[0],
    'lastname' => $name[1] ?? ' ',
    'company' => $event->user?->business_name,
    'newsletter_sign_up' => $event->user?->newsletter ?? false
];

$contact = $this->hubspot->createContact($contactSubmissionData); // [!code highlight]
```

### `createCompany()`
This method accepts an `array` and returns the newly created Contact as a `SimplePublicObject`.

This method takes the field that is stored in config (`name` by default) and performs a search for a company using the `findCompanyByKey` method. 
If a company is found, then a `CompanyAlreadyExistsException` is thrown.

#### Usage 
```PHP
$companySubmissionData = [
    'name' => $event->user?->business_name,
    'domain' => $event->user?->domain_name
];

$company = $this->hubspot->createCompany($companySubmissionData); // [!code highlight]
```

### `createNote()`
This method accepts a contact ID `int` , a note body `string` and returns a `Psr\Http\Message\ResponseInterface`.

This method creates a Note and assigns it to the provided HubSpot Contact.

***A note cannot be displayed in HubSpot without being assigned to a contact.***  

```PHP
$this->hubspot->createNote($contactId, $data['note']);
```

## Update

### `updateContact()`
This methed accepts an `array` and an `int`. The `int` is the ID of the contact in HubSpot to be updated.
It returns the updates Contact as a `SimplePublicObject`.

Unlike the `createContact()` method, this method does not perform a search for a HubSpot contact.

#### Usage
```PHP
try {
    $contact = $this->hubspot->createContact($contactSubmissionData);
} catch (ContactAlreadyExistsException $e) {
    $contact = $this->hubspot->getContactByKey('email', $contactSubmissionData['email']);
    $this->hubspot->updateContact($contactSubmissionData, $contact->getId()); // [!code highlight]
} catch (\Exception $e) {
    report($e);
}
```
In the example above, the `createContact()` and `updateConact()` methods are being used in a try catch. 
The `createContact()` throws a `ContactAlreadyExistsException` if a contact is found using the provided email address. This is then used to run the `updateContact()` method. 

## Associations
These methods were created to simply the process of associating one object with another by providing only the essental data.

### `assignOwnerToContact()`
This method accepts the ID of a contact as an `int`. A request is then sent to the HubSpot API to associate the contact with the ***Primary Contact Owner**. This can be set using a config variable `hubspot_primary_contact_owner_id`.

### `assignContactToCompany()`
This method accepts a company ID `int` and a contact ID `int`. It creates an association between the provided contact and company. This feature was adding to automate this process that otherwise would have been a manual process in the HubSpot dashboard.

## Helper Methods

### `findContactByKey()`
This methods accepts a field name `string` and the search value `string`.
It returns an array of `\HubSpot\Client\Crm\Contacts\Model\SimplePublicObject`(s). 

### `findCompanyByKey()`
This methods also accepts a field name `string` and the search value `string`.
It returns an array of `\HubSpot\Client\Crm\Companies\Model\SimplePublicObject`(s). 