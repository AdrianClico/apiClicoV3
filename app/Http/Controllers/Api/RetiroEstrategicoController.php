<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\CrmDriverInterface;
use App\Helpers\HelpFunctions;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class RetiroEstrategicoController extends Controller
{
    protected CrmDriverInterface $crm;

    // Factores fijos para calculos de pensiones
    private const FACTOR_MENSUAL_FIJO = 30.41652439;
    private const VALOR_UMA_DIARIO = 117.31;
    private const SALARIO_MINIMO_CDMX = 9582.4;

    private const TABLA_CALCULOS = [
        ['de' => 0.00, 'a' => 1.00, 'cb' => 80.00, 'ia' => 0.56],
        ['de' => 1.01, 'a' => 1.25, 'cb' => 77.11, 'ia' => 0.81],
        ['de' => 1.26, 'a' => 1.50, 'cb' => 58.18, 'ia' => 1.18],
        ['de' => 1.51, 'a' => 1.75, 'cb' => 49.23, 'ia' => 1.43],
        ['de' => 1.76, 'a' => 2.00, 'cb' => 42.67, 'ia' => 1.62],
        ['de' => 2.01, 'a' => 2.25, 'cb' => 37.65, 'ia' => 1.76],
        ['de' => 2.26, 'a' => 2.50, 'cb' => 33.68, 'ia' => 1.87],
        ['de' => 2.51, 'a' => 2.75, 'cb' => 30.48, 'ia' => 1.96],
        ['de' => 2.76, 'a' => 3.00, 'cb' => 27.83, 'ia' => 2.03],
        ['de' => 3.01, 'a' => 3.25, 'cb' => 25.60, 'ia' => 2.10],
        ['de' => 3.26, 'a' => 3.50, 'cb' => 23.70, 'ia' => 2.15],
        ['de' => 3.51, 'a' => 3.75, 'cb' => 22.07, 'ia' => 2.20],
        ['de' => 3.76, 'a' => 4.00, 'cb' => 20.65, 'ia' => 2.24],
        ['de' => 4.01, 'a' => 4.25, 'cb' => 19.39, 'ia' => 2.27],
        ['de' => 4.26, 'a' => 4.50, 'cb' => 18.29, 'ia' => 2.30],
        ['de' => 4.51, 'a' => 4.75, 'cb' => 17.30, 'ia' => 2.33],
        ['de' => 4.76, 'a' => 5.00, 'cb' => 16.41, 'ia' => 2.36],
        ['de' => 5.01, 'a' => 5.25, 'cb' => 15.61, 'ia' => 2.38],
        ['de' => 5.26, 'a' => 5.50, 'cb' => 14.88, 'ia' => 2.40],
        ['de' => 5.51, 'a' => 5.75, 'cb' => 14.22, 'ia' => 2.42],
        ['de' => 5.76, 'a' => 6.00, 'cb' => 13.62, 'ia' => 2.43],
        ['de' => 6.01, 'a' => 25.00, 'cb' => 13.00, 'ia' => 2.45],
    ];

    public function __construct(CrmDriverInterface $crm)
    {
        $this->crm = $crm;
    }

    public function enviar(Request $request): JsonResponse
    {
        $request->validate([
            'name'                 => ['required', 'string', 'min:3'],
            'email'                => ['required', 'email'],
            'phone'                => ['required', 'digits:10'],
            'servicio'             => ['required', 'string'],
            'comments'             => ['nullable', 'string'],
            'g-recaptcha-response' => ['required']
        ]);

        $recaptchaSecret = config('clients.retiroestrategico.recaptcha_secret');
        if (!HelpFunctions::verificarRecaptcha($request->input('g-recaptcha-response'), $recaptchaSecret)) {
            return response()->json(['status' => 'error', 'message' => 'Captcha inválido'], 422);
        }

        $nameParts = HelpFunctions::splitName($request->input('name'));

        $packet = [
            'firstname'     => $nameParts['firstname'],
            'lastname'      => $nameParts['lastname'],
            'email'         => $request->input('email'),
            'phone'         => $request->input('phone'),
            'custom_fields' => [
                'salutation' => $request->input('servicio'),
                'message'    => $request->input('comments'),
            ]
        ];

        $config = config('clients.retiroestrategico.hubspot');
        $result = $this->crm->submitLead($packet, $config);

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

    public function calculoLey73(Request $request): JsonResponse
    {
        $edad = (int)$request->input('edad');
        $semanasCotizadas = (int)$request->input('semanas');
        $salarioMensualPromedio = (float)$request->input('salario');

        if ($edad < 60 || $edad > 99) {
            return response()->json(['error' => 'Edad fuera de rango permitido para Ley 73 (60–99).'], 422);
        }
        if ($semanasCotizadas < 500) {
            return response()->json(['error' => 'Ley 73 requiere mínimo 500 semanas cotizadas.'], 422);
        }

        $salarioDiarioPromedio = $salarioMensualPromedio / self::FACTOR_MENSUAL_FIJO;
        $salarioVecesUMA = $salarioDiarioPromedio / self::VALOR_UMA_DIARIO;

        $tramo = $this->buscarTramo($salarioVecesUMA, self::TABLA_CALCULOS);
        if (!$tramo) {
            return response()->json(['error' => 'No se encontró tramo para el salario expresado en UMAs.'], 422);
        }

        $porcentajeCuantiaBasica = (float)$tramo['cuantia_basica_factor'];
        $porcentajeIncrementoCuantiaBasica = (float)$tramo['incremento_anual_factor'];

        $semanasParaCalculo = max(0, $semanasCotizadas - 500);
        $periodosCotizados = intdiv($semanasParaCalculo, 52);
        $semanasResiduales = $semanasParaCalculo % 52;

        $montoCuantiaBasica = $salarioMensualPromedio * $porcentajeCuantiaBasica;
        $montoIncrementoCuantia = $salarioMensualPromedio * $porcentajeIncrementoCuantiaBasica * $periodosCotizados;

        $factorResidual = ($semanasResiduales >= 13 && $semanasResiduales <= 26)
            ? ($porcentajeIncrementoCuantiaBasica * 0.5)
            : (($semanasResiduales > 26) ? $porcentajeIncrementoCuantiaBasica : 0.0);

        $montoSemanasResiduales = $salarioMensualPromedio * $factorResidual;

        $FACTOR_AYUDAS_AJUSTES = 0.2765;
        $baseParaAyudas = $montoCuantiaBasica + $montoIncrementoCuantia + $montoSemanasResiduales;
        $montoAyudasYAjustes = $baseParaAyudas * $FACTOR_AYUDAS_AJUSTES;

        $importeBrutoPension = $baseParaAyudas + $montoAyudasYAjustes;

        $factoresEdad = [60 => 0.75, 61 => 0.80, 62 => 0.85, 63 => 0.90, 64 => 0.95];
        $factorEdad = $factoresEdad[$edad] ?? ($edad >= 65 ? 1.00 : null);

        if ($factorEdad === null) {
            return response()->json(['error' => 'Edad fuera de rango para aplicar factor de Ley 73.'], 422);
        }
        $importeAjustadoPorEdad = $importeBrutoPension * $factorEdad;

        $minimoLegal = self::SALARIO_MINIMO_CDMX * 1.11;
        $montoFinal = round(max($importeAjustadoPorEdad, $minimoLegal), 2);

        return response()->json([
            'status'      => 'success',
            'monto_final' => $montoFinal,
        ]);
    }

    public function calculoMod40(Request $request): JsonResponse
    {
        $salarioDiario = (float)$request->input('salario');
        $porcentaje = 0.14438;

        return response()->json([
            'status'     => 'success',
            'mensual_28' => round($salarioDiario * 28 * $porcentaje),
            'mensual_30' => round($salarioDiario * 30 * $porcentaje),
            'mensual_31' => round($salarioDiario * 31 * $porcentaje),
            'anual'      => round($salarioDiario * 365 * $porcentaje),
        ]);
    }

    private function toScale(float $n, int $dec = 2): int
    {
        return (int)round($n * (10 ** $dec));
    }

    private function buscarTramo(float $valor, array $tabla, int $dec = 2): ?array
    {
        $v = $this->toScale($valor, $dec);
        foreach ($tabla as $row) {
            $de = $this->toScale($row['de'], $dec);
            $a = $this->toScale($row['a'], $dec);
            if ($v >= $de && $v <= $a) {
                return [
                    'de'                      => (float)$row['de'],
                    'a'                       => (float)$row['a'],
                    'cuantia_basica_pct'      => (float)$row['cb'],
                    'incremento_anual_pct'    => (float)$row['ia'],
                    'cuantia_basica_factor'   => ((float)$row['cb']) / 100.0,
                    'incremento_anual_factor' => ((float)$row['ia']) / 100.0,
                ];
            }
        }
        return null;
    }
}
