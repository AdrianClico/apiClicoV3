<?php

namespace App\Services\Crm;

use App\Contracts\CrmDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ActiveCampaignDriver implements CrmDriverInterface
{
    /**
     * Orquesta búsqueda, creación/actualización, tags y listas
     */
    public function submitLead(array $data, array $config): array
    {
        try {
            $email = strtolower(trim($data['email'] ?? ''));
            if (empty($email)) {
                throw new Exception('El email es obligatorio para ActiveCampaign.');
            }

            $baseUrl = rtrim($config['url'], '/');
            $token   = $config['token'];

            // 1. Buscar si existe el contacto en la cuenta del cliente
            $existingContact = $this->findContact($email, $config);

            // Estructurar campos personalizados para AC (Matriz de fieldValues)
            $fieldValues = [];
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                foreach ($data['custom_fields'] as $fieldId => $value) {
                    $fieldValues[] = [
                        'field' => (string) $fieldId,
                        'value' => (string) $value
                    ];
                }
            }

            $payload = [
                'name'         => $data['firstname'] ?? '',
                'lastname'     => $data['lastname'] ?? '',
                'email'        => $email,
                'phone'        => $data['phone'] ?? '',
                'fieldValues'  => $fieldValues
            ];

            if ($existingContact) {
                // 2a. Actualizar contacto existente
                $result = $this->updateContact($existingContact['id'], $payload, $config);
                $contactId = $existingContact['id'];
                $statusMsg = "Contacto existente actualizado";
            } else {
                // 2b. Crear contacto nuevo
                $result = $this->createContact($payload, $config);
                if (!$result['success']) {
                    throw new Exception($result['message']);
                }
                $contactId = $result['data']['id'] ?? null;
                $statusMsg = "Contacto nuevo creado";
            }

            if (!$contactId) {
                throw new Exception("No se pudo obtener el ID del contacto.");
            }

            // 3. Asignación masiva de Etiquetas (Tags)
            if (!empty($config['tags']) && is_array($config['tags'])) {
                $this->addTags($contactId, $config['tags'], $baseUrl, $token);
            }

            // 4. Asignación masiva de Listas
            if (!empty($config['lists']) && is_array($config['lists'])) {
                $this->addLists($contactId, $config['lists'], $baseUrl, $token);
            }

            return [
                'success' => true,
                'data'    => [
                    'message'    => $statusMsg,
                    'id'         => $contactId,
                    'raw_contact'=> $result['data'] ?? []
                ]
            ];

        } catch (Exception $e) {
            Log::error('[ActiveCampaignDriver] submitLead falló: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'ActiveCampaignDriver Error: ' . $e->getMessage()
            ];
        }
    }

    public function findContact(string $email, array $config)
    {
        $response = Http::withHeaders(['Api-Token' => $config['token']])
            ->get(rtrim($config['url'], '/') . '/contacts', ['email' => $email]);

        if ($response->failed()) {
            return null;
        }

        $contacts = $response->json('contacts');
        return (!empty($contacts)) ? $contacts[0] : null;
    }

    public function createContact(array $data, array $config): array
    {
        $response = Http::withHeaders(['Api-Token' => $config['token']])
            ->post(rtrim($config['url'], '/') . '/contacts', [
                'contact' => [
                    'firstName'   => $data['name'],
                    'lastName'    => $data['lastname'],
                    'email'       => $data['email'],
                    'phone'       => $data['phone'],
                    'fieldValues' => $data['fieldValues'] ?? []
                ]
            ]);

        if ($response->failed()) {
            return ['success' => false, 'message' => $response->body()];
        }

        return ['success' => true, 'data' => $response->json('contact')];
    }

    public function updateContact(string $id, array $data, array $config): array
    {
        $response = Http::withHeaders(['Api-Token' => $config['token']])
            ->put(rtrim($config['url'], '/') . "/contacts/{$id}", [
                'contact' => [
                    'firstName'   => $data['name'],
                    'lastName'    => $data['lastname'],
                    'fieldValues' => $data['fieldValues'] ?? []
                ]
            ]);

        if ($response->failed()) {
            return ['success' => false, 'message' => $response->body()];
        }

        return ['success' => true, 'data' => $response->json('contact')];
    }

    /**
     * Mapea a Oportunidades (Deals) con Inteligencia Anti-Duplicados por Título
     */
    public function createOpportunity(string $contactId, array $data, array $config): array
    {
        try {
            $baseUrl = rtrim($config['url'], '/');
            $token   = $config['token'];
            $title   = trim($data['title'] ?? '');

            //Buscar si el contacto ya tiene un Deal con ese mismo título
            $existingDealId = $this->findExistingDealByTitle($contactId, $title, $baseUrl, $token);

            // Reestructurar los campos personalizados del Deal para AC
            $dealFields = [];
            if (isset($data['custom_fields']) && is_array($data['custom_fields'])) {
                foreach ($data['custom_fields'] as $fieldId => $value) {
                    $dealFields[] = [
                        'customFieldId' => (string) $fieldId,
                        'fieldValue'    => (string) $value
                    ];
                }
            }

            $payload = [
                'deal' => [
                    'contact'     => (int) $contactId,
                    'description' => $title,
                    'currency'    => 'mxn',
                    'group'       => (string) ($data['group'] ?? ''),
                    'owner'       => $data['owner'] ?? 1,
                    'stage'       => (string) ($data['stage'] ?? ''),
                    'status'      => 0, // 0 = Abierto
                    'title'       => $title,
                    'value'       => $data['value'] ?? 0,
                    'fields'      => $dealFields
                ]
            ];

            if ($existingDealId) {
                // Si el título ya existe para este contacto, lo ACTUALIZAMOS en lugar de duplicar
                $response = Http::withHeaders(['Api-Token' => $token])
                    ->put("{$baseUrl}/deals/{$existingDealId}", $payload);
                $mode = "actualizado";
            } else {
                $response = Http::withHeaders(['Api-Token' => $token])
                    ->post("{$baseUrl}/deals", $payload);
                $mode = "creado";
            }

            if ($response->failed()) {
                throw new Exception($response->body());
            }

            return [
                'success' => true,
                'data'    => $response->json('deal'),
                'mode'    => $mode
            ];

        } catch (Exception $e) {
            Log::error('[ActiveCampaignDriver] createOpportunity falló: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al procesar el Deal: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Busca entre los tratos de un contacto específico si se repite el título
     */
    private function findExistingDealByTitle(string $contactId, string $title, string $baseUrl, string $token): ?string
    {
        // Consultamos los deals asociados a este contacto
        $response = Http::withHeaders(['Api-Token' => $token])
            ->get("{$baseUrl}/contacts/{$contactId}/deals");

        if ($response->failed()) {
            return null;
        }

        $deals = $response->json('deals') ?? [];

        foreach ($deals as $deal) {
            if (strtolower(trim($deal['title'] ?? '')) === strtolower($title)) {
                //Solo si esta abierto (0) se actualiza
                if ((int)$deal['status'] === 0) {
                    return (string) $deal['id'];
                }
            }
        }

        return null;
    }

    private function addTags(string $contactId, array $tags, string $baseUrl, string $token): void
    {
        foreach ($tags as $idTag) {
            Http::withHeaders(['Api-Token' => $token])->post("{$baseUrl}/contactTags", [
                'contactTag' => [
                    'contact' => $contactId,
                    'tag'     => $idTag
                ]
            ]);
        }
    }

    private function addLists(string $contactId, array $lists, string $baseUrl, string $token): void
    {
        foreach ($lists as $idList) {
            Http::withHeaders(['Api-Token' => $token])->post("{$baseUrl}/contactLists", [
                'contactList' => [
                    'list'    => $idList,
                    'contact' => $contactId,
                    'status'  => 1
                ]
            ]);
        }
    }
}
