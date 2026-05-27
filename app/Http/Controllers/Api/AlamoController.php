<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CrmDriverInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Helpers\HelpFunctions;

class AlamoController extends Controller
{
    protected CrmDriverInterface $crm;

    // Catálogo estático de atribución para las Landing Pages de Alamo B2B
    private const LANDING_PAGE_MAP = [
        'es_empresas'                     => 'Página Empresas',
        'es_empresas_alamo_arrendamiento' => 'Página Empresas B2B',
        'es_empresas_renta_flexible'      => 'Página Empresas Renta Flexible',
        'es_empresas_alamo_tipos_autos'   => 'Página Empresas Tipos de Autos',
        'es_empresas_alamo_leasing'       => 'Página Empresas Leasing',

        'en_empresas'                     => 'Página Empresas Inglés',
        'en_empresas_alamo_arrendamiento' => 'Página Empresas B2B Inglés',
        'en_empresas_renta_flexible'      => 'Página Empresas Renta Flexible Inglés',
        'en_empresas_alamo_tipos-autos'   => 'Página Empresas Tipos de Autos Inglés',
        'en_empresas_alamo_leasing'       => 'Página Empresas Leasing Inglés',
    ];

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    public function enviar(Request $request): JsonResponse
    {
        // 1. Validación del Request
        $request->validate([
            'nombre'               => 'required|string|min:3',
            'correo'               => 'required|email',
            'telefono'             => 'required',
            'telefonoOficina'      => 'required',
            'estado'               => 'required|string|min:3',
            'municipio'            => 'required|string|min:3',
            'empresa'              => 'required|string|min:3',
            'giro'                 => 'required|string|min:3',
            'puesto'               => 'required|string|min:3',
            'g-recaptcha-response' => 'required'
        ]);

        // 2. Lectura de captcha
        $recaptchaSecret = config('clients.alamo.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json([ 'status' => 'error', 'message' => 'Captcha inválido' ], 422);
        }

        // 3. Función helper para separar nombre y apellido
        $nameParts = HelpFunctions::splitName($request->input('nombre'));

        // 4. Lógica de Propiedad Municipio en HubSpot
        $campoMunicipio = 'municipio';
        $mapaCampos = [
            'Aguascalientes'                  => 'municipios_aguascalientes',
            'Baja California'                 => 'municipios_baja_california',
            'Baja California Sur'             => 'municipios_baja_california_sur',
            'Campeche'                        => 'municipios_campeche',
            'Chiapas'                         => 'municipios_chiapas',
            'Chihuahua'                       => 'municipios_chihuahua',
            'Ciudad de México'                => 'municipios_ciudad_de_mexico',
            'Coahuila de Zaragoza'            => 'municipios_coahuila',
            'Colima'                          => 'municipios_colima',
            'Durango'                         => 'municipios_durango',
            'Guanajuato'                      => 'municipios_guanajuato',
            'Guerrero'                        => 'municipios_guerrero',
            'Hidalgo'                         => 'municipios_hidalgo',
            'Jalisco'                         => 'municipios_jalisco',
            'Estado de México'                => 'municipios_estado_de_mexico',
            'Michoacán de Ocampo'             => 'municipios_michoacan',
            'Morelos'                         => 'municipios_morelos',
            'Nayarit'                         => 'municipios_nayarit',
            'Nuevo León'                      => 'municipios_nuevo_leon',
            'Oaxaca'                          => 'municipios_oaxaca',
            'Puebla'                          => 'municipios_puebla',
            'Querétaro'                       => 'municipios_queretaro',
            'Quintana Roo'                    => 'municipios_quintana_roo',
            'San Luis Potosí'                 => 'municipios_san_luis_potosi',
            'Sinaloa'                         => 'municipios_sinaloa',
            'Sonora'                          => 'municipios_sonora',
            'Tabasco'                         => 'municipios_tabasco',
            'Tamaulipas'                      => 'municipios_tamaulipas',
            'Tlaxcala'                        => 'municipios_tlaxcala',
            'Veracruz de Ignacio de la Llave' => 'municipios_veracruz',
            'Yucatán'                         => 'municipios_yucatan',
            'Zacatecas'                       => 'municipios_zacatecas',
        ];

        if (array_key_exists($request->input('estado'), $mapaCampos)) {
            $campoMunicipio = $mapaCampos[$request->input('estado')];
        }

        // 5. LP de donde proviene el lead, se hace aqui para mayor ontrol de las diferentes LP
        $landingPageKey = $request->input('landing_page');
        $pageName       = self::LANDING_PAGE_MAP[$landingPageKey] ?? 'Alamo Formulario Web';

        // 6. Mapeo de campos limpios para HubSpot (Fusión de datos con el tracking analítico interno)
        $packet = [
            'firstname'           => $nameParts['firstname'],
            'lastname'            => $nameParts['lastname'],
            'email'               => $request->input('correo'),
            'phone'               => $request->input('telefono'),
            'telefono_de_oficina' => $request->input('telefonoOficina'),
            'estados'             => $request->input('estado'),
            $campoMunicipio       => $request->input('municipio'),
            'puesto'              => $request->input('puesto'),
            'company'             => $request->input('empresa'),
            'giro_industria'      => $request->input('giro'),
            'lp_referencia'       => $landingPageKey,
            'tracking'            => [
                'hubspotutk' => $request->cookie('hubspotutk') ?? $request->input('hubspotutk'),
                'ip_address' => $request->ip(),
                'page_uri'   => $request->headers->get('referer') ?? url()->current(),
                'page_name'  => $pageName
            ]
        ];

        // 8. Configuracion del cliente
        $config = config('clients.alamo.hubspot');

        // 9. Envío a la interfaz unificada
        $result = $this->crm->submitLead($packet, $config);

        // 10. Respuesta estandarizada al frontend
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
