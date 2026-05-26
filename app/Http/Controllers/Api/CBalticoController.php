<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\LeadCaptureDriverInterface;
use App\Helpers\HelpFunctions;

class CBalticoController extends Controller
{
    protected LeadCaptureDriverInterface $leadCapture;

    public function __construct(LeadCaptureDriverInterface $leadCapture)
    {
        $this->leadCapture = $leadCapture;
    }

    /**
     * Formulario 1: Contacto / Admisiones General
     */
    public function enviar(Request $request): JsonResponse
    {
        // 1. Validación del Request
        $request->validate([
            'name'                 => 'required|string|min:3',
            'email'                => 'required|email',
            'phone'                => 'required|digits:10',
            'ciclo'                => 'required|string',
            'grado'                => 'nullable|string',
            'comments'             => 'nullable|string',
            'g-recaptcha-response' => 'required'
        ]);

        // 2. Verificar reCAPTCHA
        $recaptchaSecret = config('clients.baltico.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        // 4. Mapeo de campos limpios acoplados a las propiedades de HubSpot
        $nameParts = HelpFunctions::splitName($request->input('name'));

        // 4. Mapeo de campos limpios para HubSpot
        $leadData = [
            'firstname'     => $nameParts['firstname'],
            'lastname'      => $nameParts['lastname'],
            'email'         => $request->input('email'),
            'phone'         => $request->input('phone'),
            'annualrevenue' => $request->input('ciclo'),
            'salutation'    => $request->input('grado'),
            'message'       => $request->input('comments'),
        ];

        // 5. Cookie de seguimiento de formularios
        $trackingData = [
            'hubspotutk' => $request->cookie('hubspotutk') ?? $request->input('hubspotutk'),
            'ip_address' => $request->ip(),
            'page_uri'   => $request->headers->get('referer') ?? url()->current(),
            'page_name'  => 'Colegio Bilingüe Báltico'
        ];

        // 6. Configuración del cliente
        $config = config('clients.baltico.hubspot.contacto');

        // 7. Envío a la interfaz
        $result = $this->leadCapture->submitLead($leadData, $trackingData, $config);

        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message']],
                400);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $result['data']],
            200);
    }

    /**
     * Formulario 2: Bolsa de Trabajo
     */
    public function enviarBolsaTrabajo(Request $request): JsonResponse
    {
        // 1. Validación de campo Cargo
        if ($request->input('cargo') === 'Otro' && $request->has('cargo_otro')) {
            $request->merge([
                'cargo' => $request->input('cargo_otro')
            ]);
        }

        // 2. Validación del Request
        $request->validate([
            'name'                 => 'required|string|min:3',
            'email'                => 'required|email',
            'phone'                => 'required|digits:10',
            'cargo'                => 'required|string',
            'contacto_preferido'   => 'required|string',
            'g-recaptcha-response' => 'required'
        ]);

        // 3. Verificar reCAPTCHA
        $recaptchaSecret = config('clients.baltico.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        // 4. Separar nombre
        $nameParts = HelpFunctions::splitName($request->input('name'));

        // 5. Mapeo de campos limpios para la Bolsa de Trabajo
        $leadData = [
            'firstname'                => $nameParts['firstname'],
            'lastname'                 => $nameParts['lastname'],
            'email'                    => $request->input('email'),
            'hs_whatsapp_phone_number' => $request->input('phone'),
            'jobtitle'                 => $request->input('cargo'),
            'contacto_preferido'       => $request->input('contacto_preferido'),
        ];

        // 6. Datos analíticos y de rastreo específicos para la bolsa
        $trackingData = [
            'hubspotutk' => $request->cookie('hubspotutk') ?? $request->input('hubspotutk'),
            'ip_address' => $request->ip(),
            'page_uri'   => $request->headers->get('referer') ?? url()->current(),
            'page_name'  => 'Colegio Bilingüe Báltico Bolsa de Trabajo'
        ];

        // 7. Configuración de cliente
        $config = config('clients.baltico.hubspot.bolsa');

        // 8. Envío a la interfaz
        $result = $this->leadCapture->submitLead($leadData, $trackingData, $config);

        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message']],
                400);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $result['data']],
            200);
    }
}
