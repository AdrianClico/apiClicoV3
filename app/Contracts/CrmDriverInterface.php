<?php

namespace App\Contracts;

interface CrmDriverInterface
{
    public function submitLead(array $data, array $config): array;

    public function findContact(string $email, array $config);

    public function createContact(array $data, array $config): array;

    public function updateContact(string $id, array $data, array $config): array;

    public function createOpportunity(string $contactId, array $data, array $config): array;
}
