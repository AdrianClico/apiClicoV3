<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;

class IntegraProtectionController extends Controller
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
            'name-1'               => 'required|string|min:3',
            'email-1'              => 'required|email',
            'phone-1'              => 'required|digits:10',
            'state-1'              => 'required',
            'municipality-1'       => 'required',
            'servicio-1'           => 'required',
            'date-1'               => 'required',
            'g-recaptcha-response' => 'required'
        ]);

        // 2. Lectura de captcha
        $recaptchaSecret = config('clients.integra.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json([ 'status' => 'error', 'message' => 'Captcha inválido' ], 422);
        }

        // 3. Función helper para separar nombre y apellido
        $nameParts = HelpFunctions::splitName($request->input('name-1'));

        // 4. Mapeo de campos limpios para HubSpot (Fusión de datos con el tracking analítico interno)
        $packet = [
            'firstname'                   => $nameParts['firstname'],
            'lastname'                    => $nameParts['lastname'],
            'email'                       => $request->input('email-1'),
            'phone'                       => $request->input('phone-1'),
            'salutation'                  => $request->input('servicio-1'),
            'cuando_requiere_el_servicio' => $request->input('date-1'),
            'state'                       => $request->input('state-1'),
            'address'                     => $request->input('municipality-1'),
            'tracking'                    => [
                'hubspotutk' => $request->cookie('hubspotutk') ?? $request->input('hubspotutk'),
                'ip_address' => $request->ip(),
                'page_uri'   => $request->headers->get('referer') ?? url()->current(),
                'page_name'  => 'Integra Protection Contacto Web'
            ]
        ];

        // 6. Configuración del cliente
        $config = config('clients.integra.hubspot');

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
            'message' => 'Lead de Integra procesado correctamente',
            'data'    => $result['data']
        ], 200);
    }
}
