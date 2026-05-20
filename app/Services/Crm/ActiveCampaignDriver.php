<?php

namespace App\Services\Crm;

use App\Contracts\CrmDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActiveCampaignDriver implements CrmDriverInterface
{
    /**
     * Busca un contacto por email en ActiveCampaign.
     */
    public function findContact(string $email, array $config): ?array
    {
        $baseUrl = $config['base_url'] ?? null;
        $token   = $config['token'] ?? null;

        if (!$baseUrl || !token) {
            Log::warning("ActiveCampaign findContact llamado sin configuración válida.");
            return null;
        }

        // Hacemos la petición con reintentos automáticos por si la API parpadea
        $response = Http::withHeaders(['Api-Token' => $token])
            ->retry(3, 100)
            ->get("{$baseUrl}/api/3/contacts", ['email' => $email]);

        if ($response->failed()) {
            Log::error("ActiveCampaign findContact falló: " . $response->body());
            return null;
        }

        $contacts = $response->json('contacts');

        if (!empty($contacts)) {
            return [
                'crm_id' => $contacts[0]['id'],
                'email'  => $contacts[0]['email'],
                'raw'    => $contacts[0]
            ];
        }

        return null;
    }

    /**
     * Crea un nuevo contacto y procesa automáticamente sus listas y etiquetas.
     */
    public function createContact(array $contactData, array $config): array
    {
        $baseUrl = $config['base_url'];
        $token   = $config['token'];

        $payload = [
            'contact' => [
                'email'     => $contactData['email'],
                'firstName' => $contactData['first_name'] ?? '',
                'lastName'  => $contactData['last_name'] ?? '',
                'phone'     => $contactData['phone'] ?? '',
            ]
        ];

        if (!empty($contactData['fieldValues'])) {
            $payload['contact']['fieldValues'] = $contactData['fieldValues'];
        }

        $response = Http::withHeaders(['Api-Token' => $token])
            ->retry(3, 100)
            ->post("{$baseUrl}/api/3/contacts", $payload);

        if ($response->failed()) {
            Log::error("ActiveCampaign createContact falló: " . $response->body());
            return ['success' => false, 'message' => 'Error al crear el contacto en ActiveCampaign'];
        }

        $contactId = $response->json('contact.id');

        if (!empty($contactData['lists'])) {
            $this->syncWithLists($contactId, $contactData['lists'], $baseUrl, $token);
        }

        if (!empty($contactData['tags'])) {
            $this->syncWithTags($contactId, $contactData['tags'], $baseUrl, $token);
        }

        return [
            'success' => true,
            'crm_id'  => $contactId,
            'data'    => $response->json()
        ];
    }

    /**
     * Actualiza un contacto existente en ActiveCampaign.
     */
    public function updateContact(string $crmId, array $contactData, array $config): array
    {
        $baseUrl = $config['base_url'];
        $token   = $config['token'];

        $payload = [
            'contact' => [
                'firstName' => $contactData['first_name'] ?? '',
                'lastName'  => $contactData['last_name'] ?? '',
                'phone'     => $contactData['phone'] ?? '',
            ]
        ];

        if (!empty($contactData['fieldValues'])) {
            $payload['contact']['fieldValues'] = $contactData['fieldValues'];
        }

        // Actualizamos los datos básicos del contacto
        $response = Http::withHeaders(['Api-Token' => $token])
            ->put("{$baseUrl}/api/3/contacts/{$crmId}", $payload);

        if ($response->failed()) {
            Log::error("ActiveCampaign updateContact falló: " . $response->body());
            return ['success' => false, 'message' => 'Error al actualizar el contacto en ActiveCampaign'];
        }

        if (!empty($contactData['lists'])) {
            $this->syncWithLists($crmId, $contactData['lists'], $baseUrl, $token);
        }

        if (!empty($contactData['tags'])) {
            $this->syncWithTags($crmId, $contactData['tags'], $baseUrl, $token);
        }

        return [
            'success' => true,
            'crm_id'  => $crmId,
            'data'    => $response->json()
        ];
    }

    /**
     * Métodos auxiliares para manejar listas y etiquetas
     */
    private function syncWithLists(string $contactId, array $lists, string $baseUrl, string $token): void
    {
        foreach ($lists as $listId) {
            Http::withHeaders(['Api-Token' => $token])->post("{$baseUrl}/api/3/contactLists", [
                'contactList' => [
                    'list'    => $listId,
                    'contact' => $contactId,
                    'status'  => 1
                ]
            ]);
        }
    }

    private function syncWithTags(string $contactId, array $tags, string $baseUrl, string $token): void
    {
        foreach ($tags as $tagId) {
            Http::withHeaders(['Api-Token' => $token])->post("{$baseUrl}/api/3/contactTags", [
                'contactTag' => [
                    'contact' => $contactId,
                    'tag'     => $tagId
                ]
            ]);
        }
    }
}
