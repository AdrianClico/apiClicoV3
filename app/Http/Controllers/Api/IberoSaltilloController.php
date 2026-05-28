<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Program;
use App\Services\Crm\ActiveCampaignDriver;
use Exception;

class IberoSaltilloController extends Controller
{
    protected ActiveCampaignDriver $crm;

    public function __construct(ActiveCampaignDriver $crm)
    {
        $this->crm = $crm;
    }

    /**
     * Obtiene los programas para formularios web de forma dinámica
     */
    public function getProgramas(Request $request): JsonResponse
    {
        $nivel = $request->query('nivel');

        if (!$nivel) {
            return response()->json([
                'status' => 'error',
                'message' => 'El parámetro nivel es requerido.'
            ], 400);
        }

        $nivel = ($nivel === "Posgrado") ? "Maestría" : $nivel;

        try {
            $programas = Program::where('client_slug', 'ibero-saltillo')
                ->where('nivel_academico', trim($nivel))
                ->where('is_active', true)
                ->orderBy('nombre_interno', 'asc')
                ->get();

            return response()->json([
                'status'    => 'success',
                'nivel'     => $nivel,
                'programas' => $programas
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al consultar programas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endopint de AC que llena los campos personalizados para todos los contactos.
     */
    public function llenarCamposPrograma(Request $request): JsonResponse
    {
        if (!$request->has('contact')) {
            return response()->json(['status' => 'error', 'message' => 'No existe el parámetro contact'], 400);
        }

        try {
            $contactRequest   = $request->input('contact');
            $nivelContacto    = $contactRequest["fields"]["nivel_educativo"] ?? null;
            $programaContacto = $contactRequest["fields"]["programa"] ?? null;

            if (!$nivelContacto || !$programaContacto) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Faltan campos del programa en el payload del contacto.'
                ], 422);
            }

            $programaBD = Program::where('client_slug', 'ibero-saltillo')
                ->where('nivel_academico', trim($nivelContacto))
                ->where('nombre_interno', trim($programaContacto))
                ->where('is_active', true)
                ->first();

            if (!$programaBD) {
                return response()->json([
                    'status'  => 'error',
                    'message' => "No se encontró el programa '{$programaContacto}' activo en la base de datos."
                ], 404);
            }

            $packet = [
                'firstname'     => $contactRequest['first_name'] ?? '',
                'lastname'      => $contactRequest['last_name'] ?? '',
                'email'         => $contactRequest['email'] ?? '',
                'custom_fields' => [
                    "22" => $programaBD->fecha_inicio ?? "Consultar con Coordinación",
                    "23" => $programaBD->horario ?? "Consultar con Coordinación",
                    "24" => $programaBD->duracion ?? "Consultar con Coordinación",
                    "25" => $programaBD->modalidad ?? "Consultar con Coordinación",
                ]
            ];

            $config = config('clients.iberosaltillo.activecampaign');
            $result = $this->crm->submitLead($packet, $config);

            return response()->json([
                'status'  => 'success',
                'contact' => $result['data']
            ], 200);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Webhook de AC que genera la oportunidad en el Pipeline correcto
     * y le asigna el promotor específico del programa.
     */
    public function crearDeal(Request $request): JsonResponse
    {
        if (!$request->has('contact')) {
            return response()->json(['status' => 'error', 'message' => 'No existe el parámetro contact'], 400);
        }

        try {
            $contactRequest   = $request->input('contact');
            $idContact        = $contactRequest['id'] ?? null;
            $nivelContacto    = $contactRequest["fields"]["nivel_educativo"] ?? null;
            $programaContacto = $contactRequest["fields"]["programa"] ?? null;

            if (!$idContact || !$nivelContacto || !$programaContacto) {
                return response()->json(['status' => 'error', 'message' => 'Campos obligatorios del contacto incompletos'], 422);
            }

            $programaBD = Program::where('client_slug', 'ibero-saltillo')
                ->where('nombre_interno', trim($programaContacto))
                ->first();

            $idOwner = ($programaBD && $programaBD->crm_owner_id) ? $programaBD->crm_owner_id : 1;

            $dealConfig = config('IberoSaltilloDeals', []);

            $mapaNiveles = [
                'Maestría'     => 'maestria',
                'Licenciatura' => 'licenciatura',
                'Especialidad' => 'especialidad'
            ];

            $slugNivel = $mapaNiveles[$nivelContacto] ?? 'educacion_continua';
            $cfgPipeline = $dealConfig[$slugNivel] ?? $dealConfig['educacion_continua'];

            $dealPayload = [
                'title'         => trim($programaContacto),
                'owner'         => $idOwner,
                'group'         => $cfgPipeline['id_pipeline'],
                'stage'         => $cfgPipeline['id_stage'],
                'value'         => $programaBD ? $programaBD->precio : 0.00,
                'custom_fields' => [
                    "2" => $nivelContacto,
                    "3" => $programaContacto
                ]
            ];

            $config = config('clients.iberosaltillo.activecampaign');

            $result = $this->crm->createOpportunity((string)$idContact, $dealPayload, $config);

            return response()->json([
                'status' => 'success',
                'mode'   => $result['mode'] ?? 'procesado',
                'deal'   => $result['data']
            ], 200);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
