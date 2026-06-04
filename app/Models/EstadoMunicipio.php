<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoMunicipio extends Model
{
    protected $connection = 'helpers';

    protected $table = 'estadosmunicipios';

    public $timestamps = false;

    protected $fillable = [
        'estado',
        'municipio'
    ];
}
