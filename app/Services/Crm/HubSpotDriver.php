<?php

namespace App\Services\Crm;

use App\Contracts\CrmDriverInterface;
use App\Contracts\LeadCaptureDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HubSpotDriver implements CrmDriverInterface, LeadCaptureDriverInterface
{
    /**
     * IMPLEMENTACIÓN DE LEAD CAPTURE: API de Formularios con rastreo (Cookies/IP)
     */
    public function submitLead(array $leadData, array $trackingData, array $config): array
    {
        $portalId = $config['portal_id'];
        $formId   = $config['form_id'];

        $fields = [];
        foreach ($leadData as $key => $value) {
            if ($value !== null) {
                $fields[] = [
                    'name' => $key,
                    'value' => (string)$value
                ];
            }
        }

        $payload = [
            'fields' => $fields,
            'context' => [
                'hutk'      => $trackingData['hubspotutk'] ?? null,
                'ipAdress'  => $trackingData['ip_adress'] ?? request()->ip(),
                'pageUri'   => $trackingData['page_uri'] ?? '',
                'pageName'  => $trackingData['page_name'] ?? 'Formulario Web',
            ]
        ];

        $endpoint = "https://api.hsforms.com/submissions/v3/integration/submit/{$portalId}/{$formId}";
        $respone = Http::retry(3, 100)->post($endpoint, $payload);

        if ($respone->failed()) {
            Log::error("HubSpot submitLead falló para Portal {$portalId}. Respuesta: " . $respone->body());
            return ['success' => false, 'nessage' => 'Error al procesar el formulario con tracking analítico'];
        }

        return [
            'success' => true,
            'message' => 'Lead capturado y rastreado con éxito en HubSpot',
            'data'    => $respone->json()
        ];
    }

    /**
     * IMPLEMENTACIÓN DE CRM TRADICIONAL: Creación directa de objetos
     */
    public function createContact(array $contactData, array $config): array
    {
        $token = $config['token'] ?? null;

        if (!$token) {
            return ['success' => false, 'message' => 'Token de autenticación faltante para crear contacto'];
        }

        $payload = [
            'properties' => $contactData
        ];

        $response = Http::withToken($token)
            ->retry(3, 100)
            ->post('https://api.hubapi.com/crm/v3/objects/contacts', $payload);

        if ($response->failed()) {
            Log::error("HubSpot createContact falló: " . $response->body());
            return ['success' => false, 'message' => 'Error añ crear contacto en HubSpot'];
        }

        return [
            'success' => true,
            'crm_id'  => $response->json('id'),
            'data'    => $response->json()
        ];
    }

    /**
     * Busca un Contacto en HubSpot usando la API de obejot
     */
    public function findContact(string $email, array $config): ?array
    {
        $token = $config['token'] ?? null;

        if (!$token) {
            Log::warning("HubSpot findContact llamado sin token de autenticación.");
            return null;
        }

        $response = Http::withToken($token)
            ->post('https://api.hubapi.com/crm/v3/objects/contacts/search', [
                'filterGroups' => [[
                    'filters' => [[
                        'propertyName' => 'email',
                        'operator'     => 'EQ',
                        'value'        => $email
                    ]]
                ]]
            ]);

        if ($response->failed()) {
            Log::error("HubSpot findContact falló: " . $response->body());
            return null;
        }

        $results = $response->json('results');

        return !empty($results) ? ['crm_id' => $results[0]['id'], 'email' => $results[0]['properties']['email']] : null;
    }

    /**
     * Actualiza un contacto existente en HubSpot por su ID único.
     */
    public function updateContact(string $crmId, array $contactData, array $config): array
    {
        $token = $config['token'] ?? null;

        if (!$token) {
            return ['success' => false, 'message' => 'Token faltante para actualizar contacto.'];
        }

        $payload = ['properties' => $contactData];

        $response = Http::withToken($token)->patch("https://api.hubapi.com/crm/v3/objects/contacts/{$crmId}", $payload);

        if ($response->failed()) {
            Log::error("HubSpot updateContact falló: " . $response->body());
            return ['success' => false, 'message' => 'Error al actualizar contacto en HubSpot'];
        }

        return ['success' => true, 'crm_id' => $crmId, 'data' => $response->json()];
    }
}
