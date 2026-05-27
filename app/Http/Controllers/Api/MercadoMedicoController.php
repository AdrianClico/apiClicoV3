<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;

class MercadoMedicoController extends Controller
{
    protected CrmDriverInterface $crm;

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    public function contactRequestOdoo(Request $request): JsonResponse
    {
        // 1. Validación estricta de los datos que vienen de la landing
        $request->validate([
            'nombre'               => ['required', 'string', 'max:80'],
            'apellido'             => ['required', 'string', 'max:80'],
            'email'                => ['required', 'email', 'max:255'],
            'telefono'             => ['required', 'string', 'max:30'],
            'empresa'              => ['nullable', 'string', 'max:120'],
            'estado'               => ['required', 'string'],
            'municipio'            => ['required', 'string'],
            'es_especialista'      => ['required', 'string'],
            'especialidad'         => ['required', 'string'],
            'comentarios'          => ['nullable', 'string'],
            'pagina'               => ['required', 'string'],
            'g-recaptcha-response' => ['required']
        ]);

        // 2. Capa de Seguridad obligatoria: reCAPTCHA
        $recaptchaSecret = config('clients.mercadomedico.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        // 3. Estructuración del bloque HTML descriptivo para Odoo
        $htmlDescription = "<p>¿Eres especialista de la salud?: {$request->input('es_especialista')}</p>
                            <p>Especialidad: {$request->input('especialidad')}</p>
                            <p>Dudas o comentarios: {$request->input('comentarios')}</p>
                            <p>Este lead viene del sitio web: {$request->input('pagina')}</p>";

        // 4. Empaquetar
        $packet = [
            'firstname'     => $request->input('nombre'),
            'lastname'      => $request->input('apellido'),
            'email'         => $request->input('email'),
            'phone'         => $request->input('telefono'),
            'company'       => $request->input('empresa'),
            'custom_fields' => [
                'estado'           => $request->input('estado'),
                'municipio'        => $request->input('municipio'),
                'especialidad'     => $request->input('especialidad'),
                'html_description' => $htmlDescription
            ]
        ];

        // 5. Envio a la interfaz
        $config = config('clients.mercadomedico.odoo');
        $result = $this->crm->submitLead($packet, $config);

        // 6. Respuesta estandarizada
        if (!$result['success']) {
            return response()->json(['status' => 'error', 'message' => $result['message']], 400);
        }

        return response()->json(['status' => 'success', 'data' => $result['data']], 200);
    }
}
