<?php

namespace App\Providers;

use App\Services\Crm\ActiveCampaignDriver;
use Illuminate\Support\ServiceProvider;

use App\Contracts\CrmDriverInterface;

use App\Services\Crm\HubSpotDriver;
use App\Services\Crm\MailchimpDriver;
use App\Services\Crm\OdooDriver;

use App\Http\Controllers\Api\AlamoController;
use App\Http\Controllers\Api\CBalticoController;
use App\Http\Controllers\Api\COAMXController;
use App\Http\Controllers\Api\DarylAndradeController;
use App\Http\Controllers\Api\GrupoCorneaController;
use App\Http\Controllers\Api\GrupoJupploController;
use App\Http\Controllers\Api\HarteethController;
use App\Http\Controllers\Api\IberoSaltilloController;
use App\Http\Controllers\Api\IberoTorreonController;
use App\Http\Controllers\Api\IntegraProtectionController;
use App\Http\Controllers\Api\MaquiteckController;
use App\Http\Controllers\Api\MercadoMedicoController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // A -> Alamo (HubSpot)
        $this->app->when(AlamoController::class)
            ->needs(CrmDriverInterface::class)
            ->give(HubSpotDriver::class);

        // C -> Colegio Bilingüe Báltico (HubSpot)
        $this->app->when(CBalticoController::class)
            ->needs(CrmDriverInterface::class)
            ->give(HubSpotDriver::class);

        // C -> COAMX (HubSpot)
        $this->app->when(COAMXController::class)
            ->needs(CrmDriverInterface::class)
            ->give(HubSpotDriver::class);

        // D -> Daryl Andrade (Mailchimp)
        $this->app->when(DarylAndradeController::class)
            ->needs(CrmDriverInterface::class)
            ->give(MailchimpDriver::class);

        // G -> Grupo Córnea (HubSpot)
        $this->app->when(GrupoCorneaController::class)
            ->needs(CrmDriverInterface::class)
            ->give(HubSpotDriver::class);

        // G -> Grupo Jupplo (HubSpot)
        $this->app->when(GrupoJupploController::class)
            ->needs(CrmDriverInterface::class)
            ->give(HubSpotDriver::class);

        // H -> Harteeth (HubSpot)
        $this->app->when(HarteethController::class)
            ->needs(CrmDriverInterface::class)
            ->give(HubSpotDriver::class);

        // I -> Ibero Saltillo
        $this->app->when(IberoSaltilloController::class)
            ->needs(CrmDriverInterface::class)
            ->give(ActiveCampaignDriver::class);

        // I -> Ibero Torreón
        $this->app->when(IberoTorreonController::class)
            ->needs(CrmDriverInterface::class)
            ->give(ActiveCampaignDriver::class);

        // I -> Integra Protection (HubSpot)
        $this->app->when(IntegraProtectionController::class)
            ->needs(CrmDriverInterface::class)
            ->give(HubSpotDriver::class);

        // M -> Maquiteck (HubSpot)
        $this->app->when(MaquiteckController::class)
            ->needs(CrmDriverInterface::class)
            ->give(HubSpotDriver::class);

        // M -> Mercado Médico (Odoo)
        $this->app->when(MercadoMedicoController::class)
            ->needs(CrmDriverInterface::class)
            ->give(OdooDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
