<?php

namespace App\Services\Crm;

use App\Contracts\CrmDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class MailchimpDriver implements CrmDriverInterface
{
    /**
     * Orquesta el flujo inteligente para Mailchimp
     */
    public function submitLead(array $data, array $config): array
    {
        try {
            $email = strtolower(trim($data['email'] ?? ''));
            if (empty($email)) {
                throw new Exception('El email es obligatorio para procesar el lead en Mailchimp.');
            }

            // 1. Ejecutar la búsqueda obligatoria usando el método de la interfaz
            $contactExists = $this->findContact($email, $config);
            $msgStatus = "Contacto existente actualizado en Mailchimp";

            if ($contactExists) {
                // 2a. Si ya existe en la lista, lo actualizamos (Merge Fields / Status)
                $result = $this->updateContact($email, $data, $config);
            } else {
                // 2b. Si es nuevo, lo creamos desde cero
                $result = $this->createContact($data, $config);
                $msgStatus = "Contacto nuevo suscrito en Mailchimp";
            }

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            return [
                'success' => true,
                'data'    => $msgStatus
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'MailchimpDriver Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * BLOQUE INDIVIDUAL: Busca si el miembro ya existe en la lista usando el Hash MD5 de su email
     */
    public function findContact(string $email, array $config)
    {
        $apiKey   = $config['api_key'];
        $listId   = $config['list_id'];
        $dc       = $this->getDataCenter($apiKey);
        $subscriberHash = md5(strtolower(trim($email)));

        $endpoint = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}";

        $response = Http::withBasicAuth('user', $apiKey)->get($endpoint);

        // Si Mailchimp regresa un 404, significa de forma segura que el miembro NO existe
        if ($response->status() === 404) {
            return null;
        }

        if ($response->failed()) {
            Log::error('[MailchimpDriver] findContact falló: ' . $response->body());
            return null;
        }

        return $response->json('id') ?? null;
    }

    /**
     * BLOQUE INDIVIDUAL: Crear (Suscribir) un nuevo miembro en la lista
     */
    public function createContact(array $data, array $config): array
    {
        $apiKey = $config['api_key'];
        $listId = $config['list_id'];
        $dc     = $this->getDataCenter($apiKey);

        $endpoint = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members";

        $response = Http::withBasicAuth('user', $apiKey)->post($endpoint, [
            'email_address' => strtolower(trim($data['email'])),
            'status'        => $config['status'] ?? 'subscribed', // 'subscribed', 'pending', etc.
            'merge_fields'  => [
                'FNAME' => $data['firstname'] ?? '',
                'LNAME' => $data['lastname'] ?? '',
                'PHONE' => $data['phone'] ?? '',
                'COMPANY' => $data['company'] ?? '',
            ]
        ]);

        if ($response->failed()) {
            Log::error('[MailchimpDriver] createContact falló: ' . $response->body());
            return ['success' => false, 'message' => 'Error al suscribir el contacto en Mailchimp.'];
        }

        return ['success' => true, 'data' => $response->json()];
    }

    /**
     * BLOQUE INDIVIDUAL: Actualizar datos de un miembro existente (Se usa el email como ID)
     */
    public function updateContact(string $id, array $data, array $config): array
    {
        $apiKey = $config['api_key'];
        $listId = $config['list_id'];
        $dc     = $this->getDataCenter($apiKey);
        $subscriberHash = md5(strtolower(trim($id)));

        $endpoint = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriberHash}";

        $response = Http::withBasicAuth('user', $apiKey)->patch($endpoint, [
            'merge_fields' => [
                'FNAME' => $data['firstname'] ?? '',
                'LNAME' => $data['lastname'] ?? '',
                'PHONE' => $data['phone'] ?? '',
                'COMPANY' => $data['company'] ?? '',
            ]
        ]);

        if ($response->failed()) {
            Log::error('[MailchimpDriver] updateContact falló: ' . $response->body());
            return ['success' => false, 'message' => 'Error al actualizar el contacto en Mailchimp.'];
        }

        return ['success' => true, 'data' => $response->json()];
    }

    /**
     * FALLBACK POR CONTRATO: Mailchimp no maneja tratos/oportunidades nativos en este nivel de API
     */
    public function createOpportunity(string $contactId, array $data, array $config): array
    {
        return ['success' => true, 'message' => 'Oportunidades no aplican para Mailchimp.'];
    }

    /**
     * HELPER PRIVADO: Extrae el centro de datos (ej. us10, us21) que viene al final de la API Key
     */
    private function getDataCenter(string $apiKey): string
    {
        $parts = explode('-', $apiKey);
        return end($parts) ?: 'us1';
    }
}
