<?php

namespace Creode\LaravelHubspotForms\Contracts;

interface SubmissionInterface
{
    public function updateContact(array $userData, int $contactId);
    public function createNote(int $contactId, string $noteBody);
    public function createContact(array $userData);
}
