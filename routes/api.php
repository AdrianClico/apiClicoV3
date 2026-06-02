<?php

use App\Http\Controllers\HelpersController;
use App\Http\Controllers\Api\AlamoController;
use App\Http\Controllers\Api\CBalticoController;
use App\Http\Controllers\Api\COAMXController;
use App\Http\Controllers\Api\DarylAndradeController;
use App\Http\Controllers\Api\HarteethController;
use App\Http\Controllers\Api\HipotecaPerfectaController;
use App\Http\Controllers\Api\IberoSaltilloController;
use App\Http\Controllers\Api\IberoTorreonController;
use App\Http\Controllers\Api\IntegraProtectionController;
use App\Http\Controllers\Api\GrupoJupploController;
use App\Http\Controllers\Api\MaquiteckController;
use App\Http\Controllers\Api\MercadoMedicoController;
use App\Http\Controllers\Api\RemarController;
use App\Http\Controllers\Api\RetiroEstrategicoController;
use App\Http\Controllers\Api\TradelossaController;
use App\Http\Controllers\Api\VijusaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// 🌐 HELPERS GLOBALES PÚBLICOS
Route::prefix('helpers')->group(function () {
    Route::get('/getStates', [HelpersController::class, 'estados']);
    Route::post('/getMunicipalities', [HelpersController::class, 'municipios']);
});

// =========================================================================
// 🔒 BLOQUE DE CLIENTES SEGUROS (MANDAN TOKEN BEARER DESDE SUS FRONTENDS)
// =========================================================================

// ALAMO
Route::prefix('alamo')->middleware('client.token:alamo')->group(function () {
    Route::post('/contact', [AlamoController::class, 'enviar']);
});

// COLEGIO BILINGÜE BÁLTICO
Route::prefix('cbb')->middleware('client.token:baltico')->group(function () {
    Route::post('/contact', [CBalticoController::class, 'enviar']);
    Route::post('/contactBolsa', [CBalticoController::class, 'enviarBolsaTrabajo']);
});

// COAMX
Route::prefix('coamx')->middleware('client.token:coa')->group(function () {
    Route::post('/contact', [COAMXController::class, 'enviar']);
});

// DARYL ANDRADE
Route::prefix('daryl')->middleware('client.token:daryl')->group(function () {
    Route::post('/contact', [DarylAndradeController::class, 'register']);
    Route::post('/test', [DarylAndradeController::class, 'test']);
});

// HARTEETH
Route::prefix('harteeth')->middleware('client.token:harteeth')->group(function () {
    Route::post('/contact', [HarteethController::class, 'enviar']);
});

// HIPOTECA PERFECTA
Route::prefix('hipoteca-perfecta')->middleware('client.token:hipotecaperfecta')->group(function () {
    Route::post('/vsl-contact', [HipotecaPerfectaController::class, 'enviar']);
});

// IBERO SALTILLO (Rutas Frontend)
Route::prefix('ibero-saltillo')->middleware('client.token:iberosaltillo')->group(function () {
    Route::post('/contact', [IberoSaltilloController::class, 'saveContactForm']);
    Route::get('/programas', [IberoSaltilloController::class, 'getProgramas']);
});

// IBERO TORREÓN (Rutas Frontend)
Route::prefix('ibero-torreon')->middleware('client.token:iberotorreon')->group(function () {
    Route::post('/program', [IberoTorreonController::class, 'getProgramBySlug']);
    Route::post('/lead', [IberoTorreonController::class, 'storeLeadFromForm']);
});

// INTEGRA PROTECTION
Route::prefix('intpro')->middleware('client.token:integra')->group(function () {
    Route::post('/contact', [IntegraProtectionController::class, 'enviar']);
});

// GRUPO JUPPLO
Route::prefix('jupplo')->middleware('client.token:jupplo')->group(function () {
    Route::post('/contact', [GrupoJupploController::class, 'enviar']);
});

// MAQUITECK
Route::prefix('maquiteck')->middleware('client.token:maquiteck')->group(function () {
    Route::post('/contact', [MaquiteckController::class, 'enviar']);
});

// MERCADO MÉDICO
Route::prefix('mmedico')->middleware('client.token:mercadomedico')->group(function () {
    Route::post('/contact', [MercadoMedicoController::class, 'contactRequestOdoo']);
});

// REMAR
Route::prefix('remar')->middleware('client.token:remar')->group(function () {
    Route::post('/contact', [RemarController::class, 'enviar']);
});

// RETIRO ESTRATÉGICO
Route::prefix('retiro_estrategico')->middleware('client.token:retiroestrategico')->group(function () {
    Route::post('/contact', [RetiroEstrategicoController::class, 'enviar']);
    Route::post('/simuladorLey73', [RetiroEstrategicoController::class, 'calculoLey73']);
    Route::post('/calculadoraModalidad40', [RetiroEstrategicoController::class, 'calculoMod40']);
});

// TRADELOSSA
Route::prefix('tradelossa')->middleware('client.token:tradelossa')->group(function () {
    Route::post('/contact', [TradelossaController::class, 'enviar']);
});

// VIJUSA
Route::prefix('vijusa')->middleware('client.token:vijusa')->group(function () {
    Route::post('/contact', [VijusaController::class, 'enviar']);
});


// =========================================================================
// ZONA DE WEBHOOKS (Disparados por el CRM de ActiveCampaign)
// =========================================================================
Route::prefix('webhooks')->group(function () {
    // Webhook de Torreón
    Route::post('/ibero-torreon/activecampaign', [IberoTorreonController::class, 'webhookActiveCampaign']);

    // Webhooks de Saltillo
    Route::post('/ibero-saltillo/completar-campos', [IberoSaltilloController::class, 'llenarCamposPrograma']);
    Route::post('/ibero-saltillo/crear-trato', [IberoSaltilloController::class, 'crearDeal']);
});
