<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;

class RemarController extends Controller
{
    protected CrmDriverInterface $crm;

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    public function enviar(Request $request): JsonResponse
    {
        // 1. Validación del Request
        $request->validate([
            'firstName'            => 'required|string|min:3',
            'email'                => 'required|email',
            'phone'                => 'required|digits:10',
            'service'              => 'required',
            'comments'             => 'nullable|string',
            'g-recaptcha-response' => 'required'
        ]);

        // 2. Lectura de captcha
        $recaptchaSecret = config('clients.harteeth.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        // 3. Función helper para separar nombre y apellido
        $nameParts = HelpFunctions::splitName($request->input('firstName'));

        // 4. Mapeo de campos limpios para HubSpot (Fusión de datos con el tracking analítico interno)
        $packet = [
            'firstname'                => $nameParts['firstname'],
            'lastname'                 => $nameParts['lastname'],
            'email'                    => $request->input('email'),
            'hs_whatsapp_phone_number' => $request->input('phone'),
            'salutation'               => $request->input('service'),
            'message'                  => $request->input('comments'),
            'tracking'                 => [
                'hubspotutk' => $request->cookie('hubspotutk') ?? $request->input('hubspotutk'),
                'ip_address' => $request->ip(),
                'page_uri'   => $request->headers->get('referer') ?? url()->current(),
                'page_name'  => 'Remar Contacto Web'
            ]
        ];

        // 6. Configuración del cliente
        $config = config('clients.remar.hubspot');

        // 7. Envío a la interfaz unificada
        $result = $this->crm->submitLead($packet, $config);

        // 8. Respuesta estandarizada al frontend
        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead de Remar procesado correctamente',
            'data'    => $result['data']
        ], 200);
    }
}
