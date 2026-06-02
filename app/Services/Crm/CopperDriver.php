<?php

namespace App\Services\Crm;

use App\Contracts\CrmDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class CopperDriver implements CrmDriverInterface
{
    /**
     * Orquesta la búsqueda y decide la acción atómica
     */
    public function submitLead(array $data, array $config): array
    {
        try {
            $email = strtolower(trim($data['email'] ?? ''));
            if (empty($email)) {
                throw new Exception('El email es obligatorio para Copper.');
            }

            // 1. Mapeamos los custom fields planos al formato de estructura de Copper
            $customFields = [];
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                foreach ($data['custom_fields'] as $fieldId => $value) {
                    $customFields[] = [
                        'custom_field_definition_id' => (int) $fieldId,
                        'value' => $value
                    ];
                }
            }

            // 2. Buscamos al contacto
            $existingContact = $this->findContact($email, $config);

            // 3. Inyectamos los custom fields
            $data['custom_fields_processed'] = $customFields;

            if ($existingContact) {
                $result = $this->updateContact($existingContact['id'], $data, $config);
                $statusMsg = "Contacto existente actualizado en Copper con éxito";
            } else {
                $result = $this->createContact($data, $config);
                $statusMsg = "Contacto nuevo creado en Copper con éxito";
            }

            return [
                'success' => true,
                'data'    => [
                    'message' => $statusMsg,
                    'id'      => $result['id'] ?? $existingContact['id'] ?? null,
                    'raw'     => $result
                ]
            ];

        } catch (Exception $e) {
            Log::error('[CopperDriver] submitLead falló: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'CopperDriver Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * BÚSQUEDA
     */
    public function findContact(string $email, array $config): ?array
    {
        $response = Http::withHeaders([
            'X-PW-AccessToken' => $config['token'],
            'X-PW-Application' => 'developer_api',
            'X-PW-UserEmail'   => $config['user_email'],
            'Content-Type'     => 'application/json'
        ])->post(rtrim($config['url'], '/') . '/people/fetch_by_email', [
            'email' => $email
        ]);

        if ($response->failed()) {
            return null;
        }

        $contact = $response->json();
        return (isset($contact['id'])) ? $contact : null;
    }

    /**
     * CREACIÓN
     */
    public function createContact(array $data, array $config): array
    {
        $customFields = $data['custom_fields_processed'] ?? [];

        $payload = [
            'name' => ($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''),
            'emails' => [
                ['email' => strtolower(trim($data['email'])), 'category' => 'work']
            ],
            'phone_numbers' => [
                ['number' => $data['phone'] ?? '', 'category' => 'mobile']
            ],
            'contact_type_id' => $config['contact_type_id'] ?? 2089772,
            'assignee_id'     => $config['assignee_id'] ?? 3005,
            'custom_fields'   => $customFields
        ];

        $response = Http::withHeaders([
            'X-PW-AccessToken' => $config['token'],
            'X-PW-Application' => 'developer_api',
            'X-PW-UserEmail'   => $config['user_email'],
            'Content-Type'     => 'application/json'
        ])->post(rtrim($config['url'], '/') . '/people', $payload);

        if ($response->failed()) {
            throw new Exception('Error API Copper (Create): ' . $response->body());
        }

        return $response->json();
    }

    /**
     * ACTUALIZACIÓN
     */
    public function updateContact(string $contactId, array $data, array $config): array
    {
        $customFields = $data['custom_fields_processed'] ?? [];

        $payload = [
            'name' => ($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''),
            'emails' => [
                ['email' => strtolower(trim($data['email'])), 'category' => 'work']
            ],
            'phone_numbers' => [
                ['number' => $data['phone'] ?? '', 'category' => 'mobile']
            ],
            'contact_type_id' => $config['contact_type_id'] ?? 2089772,
            'custom_fields'   => $customFields
        ];

        $response = Http::withHeaders([
            'X-PW-AccessToken' => $config['token'],
            'X-PW-Application' => 'developer_api',
            'X-PW-UserEmail'   => $config['user_email'],
            'Content-Type'     => 'application/json'
        ])->put(rtrim($config['url'], '/') . "/people/{$contactId}", $payload);

        if ($response->failed()) {
            throw new Exception('Error API Copper (Update): ' . $response->body());
        }

        return $response->json();
    }

    /**
     * TRATO/OPORTUNIDAD
     */
    public function createOpportunity(string $contactId, array $data, array $config): array
    {
        return [
            'success' => true,
            'message' => 'Oportunidades no requeridas ni mapeadas en el flujo de Vijusa.'
        ];
    }
}
