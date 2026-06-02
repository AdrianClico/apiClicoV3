<?php

namespace App\Http\Controllers;

use App\Models\EstadoMunicipio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class HelpersController extends Controller
{
    /**
     * 1. Obtener estados únicos (Formato JSON compatible con Angular)
     */
    public function estados(): JsonResponse
    {
        try {
            $estados = EstadoMunicipio::select('estado as nombre')
                ->distinct()
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $estados
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al consultar los estados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. Obtener municipios de un estado específico
     */
    public function municipios(Request $request): JsonResponse
    {
        // Validación express: Asegura que Angular mande la propiedad 'estado'
        $request->validate([
            'estado' => ['required', 'string']
        ]);

        try {
            $estado = $request->input('estado');

            $municipios = EstadoMunicipio::select('municipio as nombre') // Estandarizamos a 'nombre' igual que los estados
            ->where('estado', trim($estado))
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'status' => 'success',
                'estado' => $estado,
                'data'   => $municipios
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al consultar los municipios: ' . $e->getMessage()
            ], 500);
        }
    }
}
