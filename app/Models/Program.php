<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'client_slug',
        'slug',
        'short_name',
        'nombre_interno',
        'area',
        'nivel_academico',
        'fecha_inicio',
        'horario',
        'modalidad',
        'duracion',
        'precio',
        'crm_owner_id',
        'is_active',
    ];

    /**
     * Casteo de tipos de datos nativos.
     */
    protected $casts = [
        'precio'    => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
