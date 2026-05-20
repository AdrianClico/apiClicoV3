<?php

namespace App\Contracts;

interface LeadCaptureDriverInterface
{
    /**
     * Captura un lead desde un formulario web vinculando datos analíticos y de rastreo.
     *
     * @param array $leadData Campos limpios del formulario (email, firstname, etc.)
     * @param array $trackingData Información de contexto web (hubspotutk, ip, url, etc.)
     * @param array $config Credenciales específicas del formulario/portal.
     * @return array Respuesta estandarizada del éxito o fallo del envío.
     */
    public function submitLead(array $leadData, array $trackingData, array $config): array;
}
