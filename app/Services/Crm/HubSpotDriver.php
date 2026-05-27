<?php

namespace App\Services\Crm;

use App\Contracts\CrmDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class HubSpotDriver implements CrmDriverInterface
{
    /**
     * Orquesta dinámicamente si envía por Formularios (v3) o si usa la API de CRM tradicional.
     */
    public function submitLead(array $data, array $config): array
    {
        // Estrategia Dinámica: Si en config viene un form_id, procesamos como Formulario con Tracking
        if (!empty($config['form_id'])) {
            return $this->submitViaForm($data, $config);
        }

        // Si no es formulario, ejecutamos el flujo tradicional del CRM: Buscar -> Crear o Actualizar
        try {
            $email = strtolower(trim($data['email'] ?? ''));
            if (empty($email)) {
                throw new Exception('El email es obligatorio para procesar el lead en HubSpot CRM.');
            }

            // 1. Buscar si el contacto ya existe
            $contact = $this->findContact($email, $config);

            if ($contact) {
                // 2a. Si existe, extraemos su crm_id y actualizamos sus datos
                $crmId  = $contact['crm_id'];
                $result = $this->updateContact((string)$crmId, $data['properties'] ?? $data, $config);
            } else {
                // 2b. Si no existe, creamos el contacto desde cero
                $result = $this->createContact($data['properties'] ?? $data, $config);
                if (!$result['success']) {
                    throw new Exception($result['message']);
                }
                $crmId = $result['crm_id'];
            }

            // 3. Crear oportunidad (Deal) de forma condicional si viene configurada
            if (!empty($config['pipeline_id']) || !empty($data['opportunity'])) {
                $oppData = $data['opportunity'] ?? $data;
                $this->createOpportunity((string)$crmId, $oppData, $config);
            }

            return [
                'success' => true,
                'message' => $contact ? 'Contacto actualizado en HubSpot CRM' : 'Contacto nuevo creado en HubSpot CRM',
                'crm_id'  => $crmId
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'HubSpotDriver CRM Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * BLOQUE INDIVIDUAL: Buscar un Contacto en HubSpot por su Email
     */
    public function findContact(string $email, array $config): ?array
    {
        $token = $config['token'] ?? null;
        if (!$token) {
            Log::warning('[HubSpotDriver] findContact llamado sin Token de autenticación.');
            return null;
        }

        $response = Http::withToken($token)
            ->post('https://api.hubapi.com/crm/v3/objects/contacts/search', [
                'filterGroups' => [[
                    'filters' => [[
                        'propertyName' => 'email',
                        'operator'     => 'EQ',
                        'value'        => strtolower(trim($email))
                    ]]
                ]]
            ]);

        if ($response->failed()) {
            Log::error('[HubSpotDriver] findContact falló: ' . $response->body());
            return null;
        }

        $results = $response->json('results');

        return !empty($results) ? [
            'crm_id' => $results[0]['id'],
            'email'  => $results[0]['properties']['email']
        ] : null;
    }

    /**
     * BLOQUE INDIVIDUAL: Crear un contacto directo en el objeto CRM
     */
    public function createContact(array $data, array $config): array
    {
        $token = $config['token'] ?? null;
        if (!$token) {
            return [ 'success' => false, 'message' => 'Token de autenticación faltante para crear contacto' ];
        }

        unset($data['tracking'], $data['opportunity']);

        $response = Http::withToken($token)
            ->retry(3, 100)
            ->post('https://api.hubapi.com/crm/v3/objects/contacts', [
                'properties' => $data
            ]);

        if ($response->failed()) {
            Log::error('[HubSpotDriver] createContact falló: ' . $response->body());
            return [ 'success' => false, 'message' => 'Error al crear contacto en el objeto CRM de HubSpot' ];
        }

        return [
            'success' => true,
            'crm_id'  => $response->json('id'),
            'data'    => $response->json()
        ];
    }

    /**
     * BLOQUE INDIVIDUAL: Actualizar un contacto existente por su ID único
     */
    public function updateContact(string $id, array $data, array $config): array
    {
        $token = $config['token'] ?? null;
        if (!$token) {
            return [ 'success' => false, 'message' => 'Token faltante para actualizar contacto.' ];
        }

        unset($data['tracking'], $data['opportunity']);

        $response = Http::withToken($token)
            ->patch("https://api.hubapi.com/crm/v3/objects/contacts/{$id}", [
                'properties' => $data
            ]);

        if ($response->failed()) {
            Log::error("[HubSpotDriver] updateContact falló para ID {$id}: " . $response->body());
            return [ 'success' => false, 'message' => 'Error al actualizar contacto en HubSpot' ];
        }

        return [ 'success' => true, 'crm_id' => $id, 'data' => $response->json() ];
    }

    /**
     * BLOQUE INDIVIDUAL: Crear Negocio/Trato (Deal) vinculado al contacto
     */
    public function createOpportunity(string $contactId, array $data, array $config): array
    {
        $token = $config['token'] ?? null;
        if (!$token) {
            return [ 'success' => false, 'message' => 'Token faltante para crear Negocio/Deal.' ];
        }

        // Creamos el Deal en HubSpot
        $dealResponse = Http::withToken($token)->post('https://api.hubapi.com/crm/v3/objects/deals', [
            'properties' => [
                'dealname'  => $data['dealname'] ?? 'Negocio Webizado',
                'pipeline'  => $config['pipeline_id'] ?? 'default',
                'dealstage' => $config['deal_stage'] ?? 'appointmentscheduled',
                'amount'    => $data['amount'] ?? '0'
            ]
        ]);

        if ($dealResponse->failed()) {
            Log::error('[HubSpotDriver] Fallo al crear el Deal: ' . $dealResponse->body());
            return [ 'success' => false, 'message' => 'No se pudo crear el trato/deal en HubSpot.' ];
        }

        $dealId = $dealResponse->json('id');

        // Asociar de forma explícita el Deal con el Contacto
        Http::withToken($token)->put("https://api.hubapi.com/crm/v3/objects/deals/{$dealId}/associations/contacts/{$contactId}/3");

        return [ 'success' => true, 'data' => [ 'deal_id' => $dealId ] ];
    }

    /**
     * LÓGICA PRIVADA: El flujo clásico de captura de formularios v3 con analítica
     */
    private function submitViaForm(array $data, array $config): array
    {
        $portalId = $config['portal_id'];
        $formId   = $config['form_id'];

        $tracking = $data['tracking'] ?? [];
        unset($data['tracking']);

        $fields = [];
        foreach ($data as $key => $value) {
            if ($value !== null) {
                $fields[] = [
                    'name'  => $key,
                    'value' => (string)$value
                ];
            }
        }

        $payload = [
            'fields'  => $fields,
            'context' => [
                'hutk'      => $tracking['hubspotutk'] ?? null,
                'ipAddress' => $tracking['ip_address'] ?? request()->ip(),
                'pageUri'   => $tracking['page_uri'] ?? '',
                'pageName'  => $tracking['page_name'] ?? 'Formulario Web',
            ]
        ];

        $endpoint = "https://api.hsforms.com/submissions/v3/integration/submit/{$portalId}/{$formId}";
        $response = Http::retry(3, 100)->post($endpoint, $payload);

        if ($response->failed()) {
            Log::error("HubSpot Form Submission falló para Portal {$portalId}. Respuesta: " . $response->body());
            return [ 'success' => false, 'message' => 'Error al procesar el formulario con tracking analítico' ];
        }

        return [
            'success' => true,
            'message' => 'Lead capturado y rastreado con éxito en HubSpot vía Formulario',
            'data'    => $response->json()
        ];
    }
}
