<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class IberoTorreonController extends Controller
{
    protected CrmDriverInterface $crm;

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    /**
     * Busca programa por slug (URL) y devuelve los datos mínimos para precargar el form.
     */
    public function getProgramBySlug(Request $request): JsonResponse
    {
        $slug = trim((string) $request->input('slug', ''));

        if ($slug === '') {
            return response()->json(['status' => 'error', 'message' => 'Slug requerido.'], 422);
        }

        $slug = preg_replace('/\.php$/i', '', $slug);

        try {
            $program = Program::where('client_slug', 'ibero-torreon')
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if (!$program) {
                return response()->json(['status' => 'error', 'message' => 'Programa no encontrado o inactivo.'], 404);
            }

            return response()->json([
                'status' => 'success',
                'msg'    => 'Programa encontrado',
                'data'   => [
                    'short_name'     => $program->short_name,
                    'nombre_interno' => $program->nombre_interno,
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Guarda un contacto desde el formulario web frontend (Usa 'short_name').
     */
    public function storeLeadFromForm(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'short_name' => ['required', 'string'],
                'email'      => ['required', 'email']
            ]);

            $program = Program::where('client_slug', 'ibero-torreon')
                ->where('short_name', trim($request->input('short_name')))
                ->where('is_active', true)
                ->first();

            if (!$program) {
                return response()->json(['status' => 'error', 'message' => 'Programa no encontrado o inactivo.'], 404);
            }

            $result = $this->processLeadWithProgram($request->all(), $program, 'form');

            return response()->json([
                'status' => 'success',
                'msg'    => 'Lead procesado correctamente',
                'data'   => $result
            ], 200);

        } catch (Exception $e) {
            $status = 400;
            $msg = $e->getMessage();

            if (stripos($msg, 'requerido') !== false) $status = 422;
            if (stripos($msg, 'no encontrado') !== false) $status = 404;

            return response()->json(['status' => 'error', 'message' => $msg], $status);
        }
    }

    /**
     * Guarda/Actualiza un contacto desde payload de ActiveCampaign o Meta (Usa 'programa_interes').
     */
    public function webhookActiveCampaign(Request $request): JsonResponse
    {
        try {
            $aryRequest = $request->all();

            if (!isset($aryRequest['contact'])) {
                return response()->json(['status' => 'error', 'message' => 'Webhook sin estructura de contacto'], 422);
            }

            $contactRequest   = $aryRequest['contact'];
            $email            = $contactRequest['email'] ?? null;
            $programaContacto = $contactRequest["fields"]["programa_interes"] ?? null;

            if (!$email) {
                return response()->json(['status' => 'error', 'message' => 'Webhook sin email'], 422);
            }

            if (!$programaContacto) {
                return response()->json(['status' => 'error', 'message' => 'Webhook sin programa'], 422);
            }

            $program = Program::where('client_slug', 'ibero-torreon')
                ->where('nombre_interno', trim($programaContacto))
                ->first();

            if (!$program) {
                return response()->json(['status' => 'error', 'message' => 'Programa no encontrado en BD: ' . $programaContacto], 404);
            }

            if (!$program->is_active) {
                return response()->json(['status' => 'error', 'message' => 'Programa inactivo: ' . $programaContacto], 404);
            }

            $normalizedData = [
                'email'    => $email,
                'nombre'   => $contactRequest['first_name'] ?? '',
                'apellido' => $contactRequest['last_name'] ?? '',
                'telefono' => $contactRequest['phone'] ?? '',
                'source'   => 'Kino Educación Continua',
            ];

            $result = $this->processLeadWithProgram($normalizedData, $program, 'activecampaign');

            return response()->json([
                'status' => 'success',
                'msg'    => 'Webhook procesado correctamente',
                'data'   => $result
            ], 200);

        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function processLeadWithProgram(array $data, Program $program, string $mode): array
    {
        $email = strtolower(trim($data['email'] ?? ''));
        if (empty($email)) {
            throw new Exception('email requerido.');
        }

        $clientCfg = config('clients.iberotorreon');
        $acCfg     = $clientCfg['activecampaign'];
        $cFields   = $clientCfg['contact_fields'];
        $dFields   = $clientCfg['deal_fields'];

        // 1. Mapear Custom Fields del Contacto
        $customFieldsMapped = [
            $cFields['departamento']    => 'Kino Educación Continua',
            $cFields['area']            => $program->area,
            $cFields['nivel_academico'] => $program->nivel_academico,
            $cFields['programa']        => $program->nombre_interno,
            // UTMs
            $cFields['utm_medium']      => $data['utm_medium'] ?? null,
            $cFields['utm_campaign']    => $data['utm_campaign'] ?? null,
            $cFields['utm_source']      => $data['utm_source'] ?? null,
            $cFields['utm_term']        => $data['utm_term'] ?? null,
            $cFields['utm_content']     => $data['utm_content'] ?? null,
            // Extras del formulario de Torreón
            $cFields['profession']      => $data['profession'] ?? $data['profesion'] ?? null,
            $cFields['comments']        => $data['comments'] ?? $data['comentarios'] ?? null,
            $cFields['pref_contact']    => $data['contacto_preferido'] ?? null,
            $cFields['another_program'] => $data['another_program'] ?? null,
        ];

        $packet = [
            'firstname'     => $data['nombre'] ?? '',
            'lastname'      => $data['apellido'] ?? '',
            'email'         => $email,
            'phone'         => $data['telefono'] ?? '',
            'custom_fields' => array_filter($customFieldsMapped)
        ];

        // 2. Resolver dinámicamente las etiquetas (Tags) por source
        $source = $data['source'] ?? 'Kino';
        $acCfg['tags'] = $clientCfg['tags_by_source'][$source] ?? [];

        $contactResult = $this->crm->submitLead($packet, $acCfg);

        if (!$contactResult['success']) {
            throw new Exception($contactResult['message']);
        }

        $contactId = $contactResult['data']['id'];

        // 3. Estructurar el Payload del Deal
        $dealPayload = [
            'title'         => $program->nombre_interno,
            'owner'         => $acCfg['owner_id'],
            'group'         => $acCfg['pipeline_id'],
            'stage'         => $acCfg['stage_id'],
            'value'         => $program->precio,
            'custom_fields' => [
                $dFields['departamento'] => 'Cursos y Diplomados',
                $dFields['area']         => $program->area,
                $dFields['oferta']       => $program->nivel_academico,
                $dFields['programa']     => $program->nombre_interno,
                $dFields['modalidad']    => $program->modalidad,
                $dFields['costo']        => $program->precio,
                $dFields['fecha_inicio'] => $program->fecha_inicio,
                $dFields['horario']      => $program->horario,
            ]
        ];

        // Crear u Oportunidad/Deal
        $dealResult = $this->crm->createOpportunity((string)$contactId, $dealPayload, $acCfg);

        return [
            'ok'         => true,
            'mode'       => $mode,
            'contact_id' => $contactId,
            'program'    => $program->nombre_interno,
            'deal'       => $dealResult,
        ];
    }
}
