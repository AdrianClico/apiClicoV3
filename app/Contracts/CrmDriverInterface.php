<?php

namespace App\Contracts;

interface CrmDriverInterface
{
    public function findContact(string $email, array $config): ?array;

    public function createContact(array $contactData, array $config): array;

    public function updateContact(string $crmId, array $contactData, array $config): array;
}
