<?php

namespace App\Providers;

use App\Http\Controllers\Api\CBalticoController;
use App\Http\Controllers\Api\COAMXController;
use App\Http\Controllers\Api\DarylAndradeController;
use App\Http\Controllers\Api\GrupoCorneaController;
use App\Http\Controllers\Api\GrupoJupploController;
use App\Http\Controllers\Api\IntegraProtectionController;
use App\Http\Controllers\Api\MaquiteckController;
use App\Services\Crm\MailchimpDriver;
use Illuminate\Support\ServiceProvider;
use App\Contracts\LeadCaptureDriverInterface;
use App\Http\Controllers\Api\AlamoController;
use App\Http\Controllers\Api\HarteethController;
use App\Services\Crm\HubSpotDriver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->when(AlamoController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(HubSpotDriver::class);

        $this->app->when(CBalticoController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(HubSpotDriver::class);

        $this->app->when(COAMXController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(HubSpotDriver::class);

        $this->app->when(DarylAndradeController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(MailchimpDriver::class);

        $this->app->when(GrupoCorneaController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(HubSpotDriver::class);

        $this->app->when(GrupoJupploController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(HubSpotDriver::class);

        $this->app->when(HarteethController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(HubSpotDriver::class);

        $this->app->when(IntegraProtectionController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(HubSpotDriver::class);

        $this->app->when(MaquiteckController::class)
            ->needs(LeadCaptureDriverInterface::class)
            ->give(HubSpotDriver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
