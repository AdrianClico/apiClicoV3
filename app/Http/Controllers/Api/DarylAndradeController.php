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
            'nombre'   => ['required', 'string', 'max:80'],
            'apellido' => ['required', 'string', 'max:80'],
            'email'    => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'empresa'  => ['nullable', 'string', 'max:120'],
            'tag_id'   => ['required', 'integer'],
            'privacy'  => ['accepted'],
        ]);

        // 2. Traducir el tag_id que viene del frontend al nombre real en Mailchimp
        $tagName = $this->tagResolver->resolveName((int)$data['tag_id']);
        if (!$tagName) {
            return response()->json([
                'status'  => 'error',
                'message' => 'tag_id no válido o no configurado.'
            ], 422);
        }

        // 3. Empaquetar dato para driver
        $packet = [
            'email'        => $data['email'],
            'merge_fields' => [
                'FNAME'   => $data['nombre'],
                'LNAME'   => $data['apellido'],
                'PHONE'   => $data['telefono'] ?? null,
                'COMPANY' => $data['empresa'] ?? null,
            ],
            'tags' => [
                'Workshop',
                $tagName
            ]
        ];

        // 4. Configuracion de Cliente
        $config = config('clients.daryl.mailchimp');
        $result = $this->crm->createContact($packet, $config);

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
            'email'  => ['required', 'email', 'max:255'],
            'perfil' => ['required'],
        ]);

        // 2. Resolver la etiqueta fija de diagnóstico (ID: 104)
        $tagName = $this->tagResolver->resolveName(104);
        if (!$tagName) {
            return response()->json([
                'status'  => 'error',
                'message' => 'tag_id no válido o no configurado.'
            ], 422);
        }

        // 3. Preparar el paquete de datos para el test
        $packet = [
            'email'        => $data['email'],
            'merge_fields' => [
                'MMERGE9' => $data['perfil'],
            ],
            'tags' => [
                $tagName
            ]
        ];

        // 4. Configuracion
        $config = config('clients.daryl.mailchimp');
        $result = $this->crm->createContact($packet, $config);

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
