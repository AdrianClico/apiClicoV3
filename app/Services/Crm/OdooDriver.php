<?php

namespace App\Services\Crm;

use App\Contracts\CrmDriverInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class OdooDriver implements CrmDriverInterface
{
    /**
     * Método principal
     */
    public function submitLead(array $data, array $config): array
    {
        try {
            $endpoint = rtrim($config['url'], '/') . '/jsonrpc';
            $db       = $config['db'];
            $password = $config['password'];

            // 1. Autenticación inicial obligatoria de Odoo
            $userId = $this->authenticate($endpoint, $db, $config['username'], $password);
            $config['user_id'] = $userId;

            // 2. Ejecutar la búsqueda
            $email     = strtolower(trim($data['email']));
            $contactId = $this->findContact($email, $config);
            $msgStatus = "Contacto existe y trato creados";

            // 3. Evaluar decisión: Si NO existe el contacto, se crea desde cero
            if (!$contactId) {
                $result = $this->createContact($data, $config);
                if (!$result['success']) {
                    throw new Exception($result['message']);
                }
                $contactId = $result['data']['id'];
                $msgStatus = "Contacto nuevo y trato creados";
            }

            // 4. Crear la Oportunidad (Trato) vinculada de forma obligatoria al Contacto
            $opportunityResult = $this->createOpportunity((string)$contactId, $data, $config);
            if (!$opportunityResult['success']) {
                throw new Exception($opportunityResult['message']);
            }

            return [
                'success' => true,
                'data'    => $msgStatus
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OdooDriver [submitLead] Error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Buscar por email
     */
    public function findContact(string $email, array $config): ?int
    {
        $endpoint = rtrim($config['url'], '/') . '/jsonrpc';

        $result = $this->executeJsonRpc(
            $endpoint, $config['db'], $config['user_id'], $config['password'],
            'res.partner', 'search_read',
            [[['email', '=', $email]]],
            ['fields' => ['id']]
        );

        return $result[0]['id'] ?? null;
    }

    /**
     * Crear Partner
     */
    public function createContact(array $data, array $config): array
    {
        $endpoint = rtrim($config['url'], '/') . '/jsonrpc';

        $contactId = $this->executeJsonRpc(
            $endpoint, $config['db'], $config['user_id'], $config['password'],
            'res.partner', 'create',
            [[
                "name"         => $data['firstname'] . " " . $data['lastname'],
                "email"        => strtolower(trim($data['email'])),
                "phone"        => $data['phone'],
                "mobile"       => $data['phone'],
                "company_name" => $data['company'] ?? ''
            ]]
        );

        if (!$contactId) {
            return ['success' => false, 'message' => 'Error al crear el contacto en Odoo.'];
        }

        return ['success' => true, 'data' => ['id' => $contactId]];
    }

    /**
     * Crear Oportunidad
     */
    public function createOpportunity(string $contactId, array $data, array $config): array
    {
        $endpoint = rtrim($config['url'], '/') . '/jsonrpc';

        $opportunityId = $this->executeJsonRpc(
            $endpoint, $config['db'], $config['user_id'], $config['password'],
            'crm.lead', 'create',
            [[
                'name'         => $data['custom_fields']['especialidad'] . " - " . $data['firstname'],
                'email_from'   => strtolower(trim($data['email'])),
                'phone'        => $data['phone'],
                'mobile'       => $data['phone'],
                'contact_name' => $data['firstname'] . " " . $data['lastname'],
                'partner_name' => $data['company'] ?? '',
                'user_id'      => $config['user_id'],
                'stage_id'     => 7, // Etapa por defecto
                'city'         => $data['custom_fields']['estado'] ?? '',
                'street2'      => $data['custom_fields']['municipio'] ?? '',
                'description'  => $data['custom_fields']['html_description'],
                'partner_id'   => (int)$contactId // El amarre con el contacto
            ]]
        );

        if (!$opportunityId) {
            return ['success' => false, 'message' => 'Error al crear la oportunidad en Odoo.'];
        }

        return ['success' => true, 'data' => ['id' => $opportunityId]];
    }

    /**
     * FALLBACKS OBLIGATORIOS POR CONTRATO
     */
    public function updateContact(string $id, array $data, array $config): array
    {
        return ['success' => false, 'message' => 'Update no implementado nativamente en Odoo.'];
    }

    /**
     * HELPER PRIVADO: Autenticación JSON-RPC
     */
    private function authenticate(string $endpoint, string $db, string $username, string $password): int
    {
        $response = Http::post($endpoint, [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'params'  => [
                'service' => 'common',
                'method'  => 'authenticate',
                'args'    => [$db, $username, $password, []]
            ],
            'id' => uniqid(),
        ]);

        $userId = $response->json('result');
        if (!$userId) throw new Exception('Credenciales incorrectas para Odoo.');

        return $userId;
    }

    /**
     * HELPER PRIVADO: Ejecutor genérico de peticiones Odoo
     */
    private function executeJsonRpc(string $endpoint, string $db, int $userId, string $password, string $model, string $method, array $args, array $additionalParams = [])
    {
        $finalArgs = empty($additionalParams) ? $args : array_merge($args, [$additionalParams]);

        $response = Http::post($endpoint, [
            'jsonrpc' => '2.0',
            'method'  => 'call',
            'params'  => [
                'service' => 'object',
                'method'  => 'execute_kw',
                'args'    => [$db, $userId, $password, $model, $method, $finalArgs]
            ],
            'id' => uniqid(),
        ]);

        return $response->json('result');
    }
}
