<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Support\DarylTagResolver;

class DarylAndradeController extends Controller
{
    protected CrmDriverInterface $crm;
    protected DarylTagResolver $tagResolver;

    public function __construct(CrmDriverInterface $crm, DarylTagResolver $tagResolver)
    {
        $this->crm         = $crm;
        $this->tagResolver = $tagResolver;
    }

    /**
     * Formulario 1: Registro general al Workshop
     */
    public function register(Request $request): JsonResponse
    {
        // 1. Validación del request
        $data = $request->validate([
            'nombre'   => [ 'required', 'string', 'max:80' ],
            'apellido' => [ 'required', 'string', 'max:80' ],
            'email'    => [ 'required', 'email', 'max:255' ],
            'telefono' => [ 'nullable', 'string', 'max:30' ],
            'empresa'  => [ 'nullable', 'string', 'max:120' ],
            'tag_id'   => [ 'required', 'integer' ],
            'privacy'  => [ 'accepted' ],
        ]);

        // 2. Traducir el tag_id que viene del frontend al nombre real en Mailchimp
        $tagName = $this->tagResolver->resolveName((int)$data['tag_id']);
        if (!$tagName) {
            return response()->json([
                'status'  => 'error',
                'message' => 'tag_id no válido o no configurado.'
            ], 422);
        }

        // 3. Empaquetar datos bajo el estándar plano de nuestra interfaz universal
        $packet = [
            'firstname'     => $data['nombre'],
            'lastname'      => $data['apellido'],
            'email'         => $data['email'],
            'phone'         => $data['telefono'] ?? null,
            'company'       => $data['empresa'] ?? null,
            'custom_fields' => [
                'tags' => [
                    'Workshop',
                    $tagName
                ]
            ]
        ];

        // 4. Configuracion de Cliente e invocación del método rey
        $config = config('clients.daryl.mailchimp');
        $result = $this->crm->submitLead($packet, $config);

        // 5. Respuesta estandarizada al frontend
        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Registro exitoso.',
            'tag'     => $tagName,
            'data'    => $result['data']
        ], 200);
    }

    /**
     * Formulario 2: Test de diagnóstico
     */
    public function test(Request $request): JsonResponse
    {
        // 1. Validación del Request
        $data = $request->validate([
            'email'  => [ 'required', 'email', 'max:255' ],
            'perfil' => [ 'required' ],
        ]);

        // 2. Resolver la etiqueta fija de diagnóstico (ID: 104)
        $tagName = $this->tagResolver->resolveName(104);
        if (!$tagName) {
            return response()->json([
                'status'  => 'error',
                'message' => 'tag_id no válido o no configurado.'
            ], 422);
        }

        // 3. Preparar el paquete plano inyectando el perfil personalizado en custom_fields
        $packet = [
            'email'         => $data['email'],
            'custom_fields' => [
                'MMERGE9' => $data['perfil'],
                'tags'    => [
                    $tagName
                ]
            ]
        ];

        // 4. Configuracion e invocación del método rey de forma agnóstica
        $config = config('clients.daryl.mailchimp');
        $result = $this->crm->submitLead($packet, $config);

        // 5. Respuesta estandarizada al frontend
        if (!$result['success']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Registro exitoso.',
            'tag'     => $tagName,
            'data'    => $result['data']
        ], 200);
    }
}
