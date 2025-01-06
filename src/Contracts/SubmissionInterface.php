<?php

namespace Creode\LaravelHubspotForms\Contracts;

interface SubmissionInterface
{
    public function updateContact(array $user, int $contactId);
    public function createNote(int $contactId, string $noteBody);
    public function createContact(array $userData);
    public function findContact(string $field, string $data);
    public function findCompany(string $field, string $data);
}
