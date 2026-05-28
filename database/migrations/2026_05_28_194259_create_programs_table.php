<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('client_slug', 50); // 'ibero-saltillo', 'ibero-torreon'
            $table->string('slug', 150);
            $table->string('short_name', 150);
            $table->string('nombre_interno', 255); // Nombre del programa en el CRM
            $table->string('area', 150)->nullable();
            $table->string('nivel_academico', 100);
            $table->string('fecha_inicio', 255)->nullable();
            $table->string('horario', 255)->nullable();
            $table->string('modalidad', 100)->nullable();
            $table->string('duracion', 100)->nullable();
            $table->decimal('precio', 10, 2)->default(0.00); // Costo numérico real
            $table->unsignedInteger('crm_owner_id')->nullable(); // ID del promotor asignado (sólo si se ocupa asignación de deals)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices para búsquedas relámpago en formularios y webhooks
            $table->index(['client_slug', 'nivel_academico', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
