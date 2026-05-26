<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\LeadCaptureDriverInterface;
use App\Helpers\HelpFunctions;

class COAMXController extends Controller
{
    protected LeadCaptureDriverInterface $leadCapture;

    public function __construct(LeadCaptureDriverInterface $leadCapture)
    {
        $this->leadCapture = $leadCapture;
    }

    public function enviar(Request $request): JsonResponse
    {
        // 1. Validación del Request
        $request->validate([
            'name'                 => 'required|string|min:3',
            'email'                => 'required|email',
            'phone'                => 'required|digits:10',
            'clinica'              => 'nullable',
            'servicio'             => 'required',
            'comentarios'          => 'nullable|string',
            'g-recaptcha-response' => 'required'
        ]);

        // 2. Lectura de captcha
        $recaptchaSecret = config('clients.coa.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        // 3. Función helper para separar nombre y apellido
        $nameParts = HelpFunctions::splitName($request->input('name'));

        // 4. Mapeo de campos limpios acoplados a las propiedades de HubSpot
        $leadData = [
            'firstname'                => $nameParts['firstname'],
            'lastname'                 => $nameParts['lastname'],
            'email'                    => $request->input('email'),
            'hs_whatsapp_phone_number' => $request->input('phone'),
            'clinica'                  => $request->input('clinica'),
            'servicio'                 => $request->input('servicio'),
            'message'                  => $request->input('comentarios'),
        ];

        // 5. Cookie de seguimiento de formularios
        $trackingData = [
            'hubspotutk' => $request->cookie('hubspotutk') ?? $request->input('hubspotutk'),
            'ip_address' => $request->ip(),
            'page_uri'   => $request->headers->get('referer') ?? url()->current(),
            'page_name'  => 'COAMX Contacto Web'
        ];

        // 6. Configuración del cliente
        $config = config('clients.coa.hubspot');

        // 7. Envío a la interfaz
        $result = $this->leadCapture->submitLead($leadData, $trackingData, $config);

        // 8. Respuesta estandarizada al frontend
        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Lead de COA procesado correctamente',
            'data'    => $result['data']
        ], 200);
    }
}
