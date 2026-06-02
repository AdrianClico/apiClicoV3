<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class VijusaController extends Controller
{
    protected CrmDriverInterface $crm;

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    public function enviar(Request $request): JsonResponse
    {
        // 1. Validación estricta del Request que viene del Frontend
        $request->validate([
            'name'                 => ['required', 'string', 'max:150'],
            'email'                => ['required', 'email'],
            'phone'                => ['required', 'string'],
            'comments'             => ['nullable', 'string'],
            'city'                 => ['nullable', 'string'],
            'g-recaptcha-response' => ['required']
        ]);

        // 2. Capa de Seguridad: Verificación de reCAPTCHA
        $recaptchaSecret = config('clients.vijusa.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        // 3. Separación de nombre
        $nameParts = HelpFunctions::splitName($request->input('name'));

        // 4. Estructuramos el Packet plano estandarizado
        $packet = [
            'firstname'     => $nameParts['firstname'],
            'lastname'      => $nameParts['lastname'],
            'email'         => $request->input('email'),
            'phone'         => $request->input('phone'),
            'custom_fields' => [
                '572323' => $request->input('comments'),
                '572328' => $request->input('city')
            ]
        ];

        // 5. Configuración de cliente
        $config = config('clients.vijusa.copper');

        // 6. Ejecución completamente agnóstica a través del contrato
        $result = $this->crm->submitLead($packet, $config);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json([
            'status'  => 'success',
            'contact' => $result['data']
        ], 200);
    }
}
