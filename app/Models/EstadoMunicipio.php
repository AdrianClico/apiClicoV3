<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoMunicipio extends Model
{
    protected $table = 'estadosmunicipios';

    public $timestamps = false;

    protected $fillable = [
        'estado',
        'municipio'
    ];

    public function getConnectionName(): string
    {
        return config('database.connections.helpers') ? 'helpers' : config('database.default');
    }
}
