<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class TradelossaController extends Controller
{
    protected CrmDriverInterface $crm;

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    public function enviar(Request $request): JsonResponse
    {
        $formKey = $request->input('form_key');

        // 1. Validación estricta de todos los campos que pueden venir del frontend
        $request->validate([
            'name'                 => ['required', 'string', 'min:3'],
            'lastname'             => ['required', 'string', 'min:3'],
            'empresa'              => ['required', 'string', 'min:3'],
            'email'                => ['required', 'email'],
            'phone'                => ['required', 'digits:10'],
            'servicio'             => ['required', 'string'],
            'sector'               => ['nullable', 'string'],
            'ciudad_or'            => ['nullable', 'string'],
            'estado_or'            => ['nullable', 'string'],
            'pais_or'              => ['nullable', 'string'],
            'pais_des'             => ['nullable', 'string'],
            'ciudad_des'           => ['nullable', 'string'],
            'estado_des'           => ['nullable', 'string'],
            'largo'                => ['nullable', 'numeric', 'digits_between:1,4'],
            'alto'                 => ['nullable', 'numeric', 'digits_between:1,4'],
            'ancho'                => ['nullable', 'numeric', 'digits_between:1,4'],
            'peso'                 => ['nullable', 'numeric', 'digits_between:1,4'],
            'comments'             => ['nullable', 'string'],
            'g-recaptcha-response' => ['required']
        ]);

        // 2. Capa de Seguridad: Validación de reCAPTCHA
        $recaptchaSecret = config('clients.tradelossa.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        // 3. Mapeo base
        $formId = config('clients.tradelossa.hubspot.form_contacto');
        $pageName = 'Formulario de contacto';

        $customFieldsMapped = [
            'servicio' => $request->input('servicio'),
        ];

        // 4. Si es Cotización, extendemos los custom fields y cambiamos el Form ID dinámicamente
        if ($formKey === "form-cotizacion") {
            $formId = config('clients.tradelossa.hubspot.form_cotizacion');
            $pageName = 'Formulario de Cotización';

            $customFieldsMapped += [
                'industry'       => $request->input('sector'),
                'city'           => $request->input('ciudad_or'),
                'state'          => $request->input('estado_or'),
                'country'        => $request->input('pais_or'),
                'pais_destino'   => $request->input('pais_des'),
                'ciudad_destino' => $request->input('ciudad_des'),
                'estado_destino' => $request->input('estado_des'),
                'largo'          => $request->input('largo'),
                'alto'           => $request->input('alto'),
                'ancho'          => $request->input('ancho'),
                'peso'           => $request->input('peso'),
                'message'        => $request->input('comments'),
            ];
        }

        // 5. Empaquetar el Packet
        $packet = [
            'firstname'     => $request->input('name'),
            'lastname'      => $request->input('lastname'),
            'email'         => $request->input('email'),
            'phone'         => $request->input('phone'),
            'company'       => $request->input('empresa'),
            'custom_fields' => array_filter($customFieldsMapped)
        ];

        // 6. Configuracion
        $config = [
            'portal_id' => config('clients.tradelossa.hubspot.portal_id'),
            'form_id'   => $formId,
            'page_name' => $pageName
        ];

        // 7. Envío a la interfaz
        $result = $this->crm->submitLead($packet, $config);

        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json(['status' => 'success', 'data' => $result['data']], 200);
    }
}
