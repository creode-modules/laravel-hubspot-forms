<?php

namespace Creode\LaravelHubspotForms\Contracts;

interface SubmissionInterface
{
    public function updateContact(array $user, int $contactId);
    public function createNote(int $contactId, string $noteBody);
    public function createContact(array $userData);
    public function find(string $field, string $data);
}
