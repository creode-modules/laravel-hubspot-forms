<?php

namespace Creode\LaravelHubspotForms\Contracts;

interface SubmissionInterface
{
    public function update($user, $contactId);
    public function find($field, $email);
}
