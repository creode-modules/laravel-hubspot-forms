<?php

namespace Creode\LaravelHubspotForms\Contracts;

interface SubmissionInterface
{
    public function updateUser($user, $contactId);
    public function createNote($contactId, $noteBody);
    public function createContact($userData);
    public function find($field, $email);
}
