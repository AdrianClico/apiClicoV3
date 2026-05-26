<?php


namespace App\Services\Crm;


use App\Contracts\CrmDriverInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class MailchimpDriver implements CrmDriverInterface
{
    public function createContact(array $contactData, array $config): array
    {
        try {
            $apiKey = $config['api_key'];
            $server = $config['server'];
            $audienceId = $config['audience_id'];
            $email = strtolower(trim($contactData['email']));

            $subscriberHash = md5($email);

            // 1. Petición para crear o actualizar el contacto (Suscripción)
            $response = Http::withBasicAuth('user', $apiKey)
                ->put("https://{$server}.api.mailchimp.com/3.0/lists/{$audienceId}/members/{$subscriberHash}", [
                    'email_address' => $email,
                    'status_if_new' => 'subscribed',
                    'status'        => 'subscribed',
                    'merge_fields'  => $data['merge_fields'] ?? []
                ]);

            if (!$response->successful()) {
                throw new Exception("Error en Mailchimp (Member): " . $response->body());
            }

            $memberData = $response->json();

            // 2. Enviar etiquetas para segmentacion
            if (!empty($data['tags'])) {
                $formattedTags = array_map(function ($tagName) {
                    return ['name' => $tagName, 'status' => 'active'];
                }, $data['tags']);

                $tagsResponse = Http::withBasicAuth('user', $apiKey)
                    ->post("https://{$server}.api.mailchimp.com/3.0/lists/{$audienceId}/members/{$subscriberHash}/tags", [
                        'tags' => $formattedTags
                    ]);

                if (!$tagsResponse->successful()) {
                    throw new Exception("Error al aplicar etiquetas en Mailchimp: " . $tagsResponse->body());
                }
            }

            return [
                'success' => true,
                'data'    => [
                    'status' => $memberData['status'] ?? 'subscribed',
                    'email'  => $email
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function findContact(string $email, array $config): array { return ['success' => false]; }
    public function updateContact(string $email, array $data, ?array $config): array { return ['success' => false]; }
}
