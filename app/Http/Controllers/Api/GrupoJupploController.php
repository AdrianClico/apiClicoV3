<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;

class GrupoJupploController extends Controller
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
            'name'                 => 'required|string|min:3',
            'email'                => 'required|email',
            'phone'                => 'required|digits:10',
            'servicio'             => 'required',
            'horario'              => 'required',
            'comentarios'          => 'nullable|string',
            'g-recaptcha-response' => 'required'
        ]);

        // 2. Lectura de captcha
        $recaptchaSecret = config('clients.jupplo.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json([ 'status' => 'error', 'message' => 'Captcha inválido' ], 422);
        }

        // 3. Función helper para separar nombre y apellido
        $nameParts = HelpFunctions::splitName($request->input('name'));

        // 4. Mapeo de campos limpios para HubSpot (Fusión de datos con el tracking analítico interno)
        $packet = [
            'firstname'                                   => $nameParts['firstname'],
            'lastname'                                    => $nameParts['lastname'],
            'email'                                       => $request->input('email'),
            'phone'                                       => $request->input('phone'),
            'servicio'                                    => $request->input('servicio'),
            'en_que_horario_prefieres_que_te_contactemos' => $request->input('horario'),
            'message'                                     => $request->input('comentarios'),
            'tracking'                                    => [
                'hubspotutk' => $request->cookie('hubspotutk') ?? $request->input('hubspotutk'),
                'ip_address' => $request->ip(),
                'page_uri'   => $request->headers->get('referer') ?? url()->current(),
                'page_name'  => 'Jupplo Contacto Web'
            ]
        ];

        // 6. Configuración del cliente
        $config = config('clients.jupplo.hubspot');

        // 7. Envío a la interfaz
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
            'message' => 'Lead de Jupplo procesado correctamente',
            'data'    => $result['data']
        ], 200);
    }
}
