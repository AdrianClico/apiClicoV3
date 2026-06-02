<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HipotecaPerfectaController extends Controller
{
    protected CrmDriverInterface $crm;

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    public function enviar(Request $request): JsonResponse
    {
        // 1. Validación estricta del Request que viene de la Landing
        $request->validate([
            'name'                 => ['required', 'string', 'min:3', 'max:150'],
            'email'                => ['required', 'email'],
            'phone'                => ['required', 'string'],
            'situacion'            => ['nullable', 'string'],
            'monto'                => ['nullable', 'string'],
            'g-recaptcha-response' => ['required']
        ]);

        // 2. Capa de Seguridad: Validación de reCAPTCHA
        $recaptchaSecret = config('clients.hipotecaperfecta.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        // 3. Rompemos el nombre completo de forma limpia (Nombre y Apellido)
        $nameParts = HelpFunctions::splitName($request->input('name'));

        // 4. Mapeo estandarizado de Custom Fields para ActiveCampaign
        $customFieldsMapped = [
            '27' => $request->input('utm_campaign'),
            '28' => $request->input('utm_source'),
            '29' => $request->input('utm_medium'),
            '30' => $request->input('utm_term'),
            '31' => $request->input('utm_content'),
            '24' => $request->input('situacion'),
            '25' => $request->input('monto'),
        ];

        // 5. Packet
        $packet = [
            'firstname'     => $nameParts['firstname'],
            'lastname'      => $nameParts['lastname'],
            'email'         => $request->input('email'),
            'phone'         => $request->input('phone'),
            'custom_fields' => array_filter($customFieldsMapped)
        ];

        // 6. Configuración de cliente
        $config = config('clients.hipotecaperfecta.activecampaign');

        // 7. Ejecución completamente agnóstica a través de la interfaz
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
