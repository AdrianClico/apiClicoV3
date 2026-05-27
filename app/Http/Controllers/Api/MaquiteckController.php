<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;

class MaquiteckController extends Controller
{
    protected CrmDriverInterface $crm;

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    public function enviar(Request $request): JsonResponse
    {
        // 1. Validación estricta del Request
        $request->validate([
            'name'                    => 'required|string|min:3',
            'email'                   => 'required|email',
            'phone'                   => 'required|digits:10',
            'empresa'                 => 'nullable|string',
            'zona'                    => 'required|string|min:3',
            'servicio'                => 'required|string',
            'periodo'                 => 'nullable|string',
            'comments'                => 'nullable|string',
            'martillo'                => 'nullable|in:Si,No',
            'combustible'             => 'nullable|in:Si,No',
            'metros_demoliciones'     => 'nullable|numeric|min:0',
            'metros_excavaciones'     => 'nullable|numeric|min:0',
            'area_terracerias'        => 'nullable|numeric|min:0',
            'profundidad_terracerias' => 'nullable|numeric|min:0',
            'acarreo_metros'          => 'nullable|numeric|min:0',
            'tiro'                    => 'nullable|string',
            'g-recaptcha-response'    => 'required'
        ]);

        // 2. Filtro de Seguridad: reCAPTCHA centralizado
        $recaptchaSecret = config('clients.maquiteck.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json([ 'status' => 'error', 'message' => 'Captcha inválido' ], 422);
        }

        // 3. Procesar y segmentar nombre completo
        $nameParts = HelpFunctions::splitName($request->input('name'));

        // 4. Lógica de negocio dinámica: Combinar metros según el tipo de servicio seleccionado
        $metrosValue = null;
        if ($request->filled('metros_demoliciones')) {
            $metrosValue = $request->input('metros_demoliciones') . ' m²';
        } elseif ($request->filled('metros_excavaciones')) {
            $metrosValue = $request->input('metros_excavaciones') . ' m³';
        } elseif ($request->filled('area_terracerias')) {
            $metrosValue = $request->input('area_terracerias') . ' m²';
        }

        // 5. Mapeo de campos limpios para HubSpot (Fusión de datos con el tracking analítico interno)
        $packet = [
            'firstname'               => $nameParts['firstname'],
            'lastname'                => $nameParts['lastname'],
            'email'                   => $request->input('email'),
            'phone'                   => $request->input('phone'),
            'city'                    => $request->input('zona'),
            'company'                 => $request->input('empresa'),
            'servicio'                => $request->input('servicio'),
            'agregar_martillo'        => $request->input('martillo'),
            'incluir_combustible'     => $request->input('combustible'),
            'periodo_de_renta'        => $request->input('periodo'),
            'message'                 => $request->input('comments'),
            'profundidad_terracerias' => $request->input('profundidad_terracerias'),
            'acarreo_metros'          => $request->input('acarreo_metros'),
            'tipo_de_tiro'            => $request->input('tiro'),
            'metros'                  => $metrosValue,
            'tracking'                => [
                'hubspotutk' => $request->cookie('hubspotutk') ?? $request->input('hubspotutk'),
                'ip_address' => $request->ip(),
                'page_uri'   => $request->headers->get('referer') ?? url()->current(),
                'page_name'  => 'Formulario de contacto Maquiteck'
            ]
        ];

        // 7. Configuración del cliente
        $config = config('clients.maquiteck.hubspot');

        // 8. Envío a la interfaz unificada
        $result = $this->crm->submitLead($packet, $config);

        // 9. Respuesta estandarizada
        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $result['data']
        ], 200);
    }
}
